<?php

declare(strict_types=1);

namespace ListenNotes\PodcastApi\Tests;

use ListenNotes\PodcastApi\{ApiMethods, Client, Http\Curl, Exception};
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

// Exercise the real transport. The last step before connecting enforces loopback.
final class FixtureClient extends Curl
{
    use ApiMethods;

    public function __construct(string $origin, ?string $key = null, $timeout = 30)
    {
        parent::__construct($key, $timeout);
        $this->_strHost = $origin;
    }

    protected function sendRequest(string $method, string $url, ?string $body = null): string
    {
        if (!str_starts_with($this->_strHost, 'http://127.0.0.1:')
            || !str_starts_with($url, $this->_strHost . '/')) {
            throw new \LogicException('Offline tests must only contact the local fixture');
        }
        return parent::sendRequest($method, $url, $body);
    }

    public function exampleRequest(string $method, array $params): string
    {
        return $this->requestApi($method, '/example/{id}', ['page'], $params);
    }

    public function parseExampleHeaders(string $headers): array { return $this->parseHeaderBlock($headers); }

    public function parseExampleRequest(string $raw): array
    {
        $this->_objInfo->request_header = $raw;
        return $this->parseRequestHeaders();
    }
}

final class PodcastApiTest extends TestCase
{
    private static $server;
    private static string $origin;
    private static string $log;
    private static string $serverLog;
    private static array $proxies;
    private FixtureClient $client;

    public static function contract(): array
    {
        return json_decode(file_get_contents(dirname(__DIR__) . '/listennotes/api-contract.json'), true, 512, JSON_THROW_ON_ERROR);
    }

    public static function setUpBeforeClass(): void
    {
        self::$proxies = [];
        foreach (['http_proxy', 'https_proxy', 'all_proxy', 'HTTP_PROXY', 'HTTPS_PROXY', 'ALL_PROXY'] as $name) {
            self::$proxies[$name] = getenv($name);
            putenv($name);
        }
        $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
        if ($socket === false) { throw new \RuntimeException($error); }
        $address = stream_socket_get_name($socket, false);
        fclose($socket);
        self::$origin = 'http://' . $address;
        self::$log = tempnam(sys_get_temp_dir(), 'php-sdk-requests-');
        self::$serverLog = tempnam(sys_get_temp_dir(), 'php-sdk-server-');
        self::$server = proc_open([PHP_BINARY, '-S', $address, __DIR__ . '/fixtures/router.php'],
            [['file', '/dev/null', 'r'], ['file', self::$serverLog, 'a'], ['file', self::$serverLog, 'a']],
            $pipes, __DIR__, ['SDK_FIXTURE_LOG' => self::$log]);
        for ($attempt = 0; $attempt < 100; $attempt++) {
            $connection = @stream_socket_client('tcp://' . $address, $errno, $error, 0.05);
            if ($connection !== false) { fclose($connection); return; }
            usleep(20000);
        }
        throw new \RuntimeException('Local HTTP fixture did not start: ' . file_get_contents(self::$serverLog));
    }

    public static function tearDownAfterClass(): void
    {
        if (is_resource(self::$server)) { proc_terminate(self::$server); proc_close(self::$server); }
        foreach ([self::$log, self::$serverLog] as $path) { unlink($path); }
        foreach (self::$proxies as $name => $value) { putenv($value === false ? $name : "$name=$value"); }
    }

    protected function setUp(): void
    {
        $this->client = new FixtureClient(self::$origin);
        file_put_contents(self::$log, '');
    }

    public static function operations(): iterable
    {
        foreach (self::contract()['operations'] as $op) { yield $op['func'] => [$op]; }
    }

    #[DataProvider('operations')]
    public function testEveryGeneratedMethod(array $op): void
    {
        $params = $op['example_params'];
        $response = $this->client->{$op['func']}($params);
        self::assertIsString($response);
        $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame($op['method'], $data['method']);
        $path = preg_replace_callback('/\{([^}]+)\}/', static function ($match) use (&$params) {
            $value = $params[$match[1]];
            unset($params[$match[1]]);
            return rawurlencode((string) $value);
        }, $op['path']);
        self::assertSame('/api/v2' . $path, parse_url($data['uri'], PHP_URL_PATH));
        $queryNames = array_column(array_filter($op['parameters'], fn ($p) => $p['in'] === 'query'), 'name');
        $query = $body = [];
        foreach ($params as $name => $value) {
            if (in_array($op['method'], ['GET', 'DELETE'], true) || in_array($name, $queryNames, true)) {
                $query[$name] = (string) $value;
            } else { $body[$name] = (string) $value; }
        }
        self::assertSame($query, $data['query']);
        self::assertSame($body, $data['form']);
        self::assertSame($op['method'], $this->client->getMethod());
        self::assertSame($data['uri'], $this->client->getUri());
        self::assertSame($data['body'], $this->client->getRequestBody());
        self::assertSame(200, $this->client->getStatusCode());
        self::assertSame('17', $this->client->getHeaders()['x-listenapi-usage']);
        self::assertSame('podcast-api-php 3.0.0', $data['headers']['user-agent']);
        if (in_array($op['method'], ['POST', 'PUT'], true)) {
            self::assertSame('application/x-www-form-urlencoded', $data['headers']['content-type']);
        } else { self::assertSame('', $data['body']); }
    }

    public function testPublicClientRetainsMethodsAndConfiguration(): void
    {
        $first = new Client('first-key');
        $second = new Client('second-key');
        self::assertSame('first-key', $first->getRequestHeader('x-listenapi-key'));
        self::assertSame('second-key', $second->getRequestHeader('X-ListenAPI-Key'));
        self::assertSame('https://listen-api.listennotes.com/api/v2/search', $first->getAction('search'));
        foreach ([new Client(), new Client(null), new Client('')] as $client) {
            self::assertSame('https://listen-api-test.listennotes.com/api/v2/search', $client->getAction('search'));
            self::assertSame(0, $client->getStatusCode());
            self::assertSame([], $client->getHeaders());
        }
        foreach (self::contract()['operations'] as $op) { self::assertTrue(method_exists($first, $op['func'])); }
    }

    public function testEncodingEmptyAndOmittedFields(): void
    {
        $data = json_decode($this->client->search(['q' => 'café & a/b? #+', 'offset' => 0,
            'safe_mode' => false, 'language' => '', 'unused' => null]), true);
        self::assertSame('/api/v2/search?q=caf%C3%A9%20%26%20a%2Fb%3F%20%23%2B&offset=0&safe_mode=0&language=', $data['uri']);
        $params = ['id' => 'a/b ?#%+é', 'item_id' => 'x/y ?#%+', 'notes' => ''];
        $before = $params;
        $data = json_decode($this->client->updatePlaylistItemNotes($params), true);
        self::assertSame($before, $params);
        self::assertSame('/api/v2/playlists/a%2Fb%20%3F%23%25%2B%C3%A9/items/x%2Fy%20%3F%23%25%2B', $data['uri']);
        self::assertSame('notes=', $data['body']);
        $data = json_decode($this->client->updatePlaylist(['id' => 'abc', 'description' => '']), true);
        self::assertSame('description=', $data['body']);
        $data = json_decode($this->client->addPlaylistItem(['id' => 'abc', 'episode_id' => 'episode', 'notes' => null]), true);
        self::assertSame('episode_id=episode', $data['body']);
        foreach (['POST', 'PUT'] as $method) {
            $data = json_decode($this->client->exampleRequest($method,
                ['id' => 'abc', 'page' => 0, 'description' => '', 'notes' => 'hi & café', 'flag' => false]), true);
            self::assertSame('/api/v2/example/abc?page=0', $data['uri']);
            self::assertSame('description=&notes=hi%20%26%20caf%C3%A9&flag=0', $data['body']);
        }
        $data = json_decode($this->client->deletePodcast(['id' => 'abc', 'reason' => 'remove & please']), true);
        self::assertSame('/api/v2/podcasts/abc?reason=remove%20%26%20please', $data['uri']);
        self::assertSame('', $data['body']);
    }

    public function testMissingIdentifiersFailBeforeConnecting(): void
    {
        foreach ([[], ['id' => null], ['id' => ''], ['id' => 'abc'], ['id' => 'abc', 'item_id' => false]] as $params) {
            try { $this->client->deletePlaylistItem($params); self::fail('Missing identifier was accepted'); }
            catch (Exception\InvalidRequestException $e) { self::assertStringContainsString('path parameter', $e->getMessage()); }
        }
        self::assertSame('', file_get_contents(self::$log));
    }

    public function testRequestStateAndHeadersDoNotLeak(): void
    {
        $first = new FixtureClient(self::$origin, 'first-key');
        $second = new FixtureClient(self::$origin, 'second-key');
        $first->setRequestHeader('User-Agent', 'first-agent');
        $first->setRequestHeader('X-Test', 'before: value');
        $first->setRequestHeader('x-test', 'after: value');
        foreach ([['createPlaylist', ['name' => 'test'], 'POST'], ['search', ['q' => 'test'], 'GET'],
            ['deletePlaylistItem', ['id' => 'abc', 'item_id' => 1], 'DELETE'], ['search', ['q' => 'test'], 'GET'],
            ['updatePlaylist', ['id' => 'abc', 'description' => ''], 'PUT'], ['search', ['q' => 'test'], 'GET']] as [$method, $params, $http]) {
            $data = json_decode($first->$method($params), true);
            self::assertSame($http, $data['method']);
            self::assertSame('first-key', $data['headers']['x-listenapi-key']);
            self::assertSame('after: value', $data['headers']['x-test']);
            self::assertSame('first-agent', $data['headers']['user-agent']);
            if ($http === 'GET' || $http === 'DELETE') {
                self::assertSame('', $data['body']);
                self::assertArrayNotHasKey('content-type', $data['headers']);
            }
            $other = json_decode($second->fetchPodcastGenres(), true);
            self::assertSame('second-key', $other['headers']['x-listenapi-key']);
            self::assertArrayNotHasKey('x-test', $other['headers']);
        }
        self::assertSame('after: value', $first->parseRequestHeaders()['x-test']);
        self::assertSame('2026-09-26T17:27:33.110641+00:00', $first->getHeaders()['x-listenapi-nextbillingdate']);
        self::assertSame(['x-final' => 'a: b', 'empty' => ''], $first->parseExampleHeaders(
            "HTTP/1.1 100 Continue\r\nX-Old: old\r\n\r\nHTTP/2 200\r\nX-Final:a: b\r\nEmpty:\r\n\r\n"));
        self::assertSame(['x-test' => 'proxy: header'], $first->parseExampleRequest(
            "GET http://example.test/path HTTP/1.1\r\nX-Test: proxy: header\r\n\r\n"));
    }

    public static function successStatuses(): array { return [[200], [201], [202], [204]]; }

    #[DataProvider('successStatuses')]
    public function testSuccessfulStatuses(int $status): void
    {
        $response = $this->client->post(self::$origin . '/?status=' . $status, ['notes' => '']);
        self::assertIsString($response);
        self::assertSame($status, $this->client->getStatusCode());
    }

    public static function errors(): array
    {
        return [[400, Exception\InvalidRequestException::class], [401, Exception\AuthenticationException::class],
            [403, Exception\PermissionDeniedException::class], [404, Exception\NotFoundException::class],
            [429, Exception\RateLimitException::class], [500, Exception\ListenApiException::class],
            [302, Exception\ListenApiException::class], [307, Exception\ListenApiException::class]];
    }

    #[DataProvider('errors')]
    public function testErrorsPreserveDetailsAndNeverRetryOrRedirect(int $status, string $class): void
    {
        try { $this->client->post(self::$origin . '/?status=' . $status, ['name' => 'test']); self::fail('Expected API error'); }
        catch (Exception\ListenApiException $e) {
            self::assertInstanceOf($class, $e);
            self::assertSame($status, $e->getStatus());
            self::assertSame($status, $e->getCode());
            self::assertSame('17', $e->getResponseHeaders()['x-listenapi-usage']);
            self::assertStringContainsString('HTTP ' . $status, $e->getMessage());
            if ($status >= 400) {
                self::assertStringContainsString('Specific API error', $e->getMessage());
                self::assertSame('{"error":"Specific API error"}', $e->getResponseBody());
            }
        }
        self::assertCount(1, file(self::$log));
    }

    public function testNonJsonErrorsAndExceptionDefaults(): void
    {
        try { $this->client->get(self::$origin . '/?status=502&format=html'); self::fail('Expected API error'); }
        catch (Exception\ListenApiException $e) {
            self::assertSame(502, $e->getStatus());
            self::assertSame('<html>unavailable</html>', $e->getResponseBody());
        }
        self::assertSame(0, (new Exception\ListenApiException())->getStatus());
        self::assertSame(400, (new Exception\InvalidRequestException())->getStatus());
    }

    public function testTimeoutsWrapConnectionFailureAndClearResponseState(): void
    {
        $client = new FixtureClient(self::$origin, null, 0.05);
        $client->search(['q' => 'ok']);
        try { $client->get(self::$origin . '/?delay=1'); self::fail('Expected timeout'); }
        catch (Exception\APIConnectionException $e) {
            self::assertSame(0, $e->getStatus());
            self::assertSame(0, $client->getStatusCode());
            self::assertSame([], $client->getHeaders());
            self::assertSame('', $e->getResponseBody());
        } finally { usleep(180000); }
        self::assertCount(2, file(self::$log));
    }

    public function testInvalidTimeoutsAndHeaders(): void
    {
        foreach ([0, -1, null, '30', INF, NAN] as $timeout) {
            try { new Client(null, $timeout); self::fail('Invalid timeout accepted'); }
            catch (\InvalidArgumentException $e) { self::assertStringContainsString('timeout', $e->getMessage()); }
        }
        foreach ([["Bad\nHeader", 'value'], ['X-Test', "bad\r\nvalue"]] as [$name, $value]) {
            try { $this->client->setRequestHeader($name, $value); self::fail('Invalid header accepted'); }
            catch (\InvalidArgumentException $e) { self::assertSame('Invalid request header', $e->getMessage()); }
        }
    }

    public function testReadmeContractAndExamples(): void
    {
        $readme = file_get_contents(dirname(__DIR__) . '/README.md');
        $names = [];
        foreach (self::contract()['operations'] as $op) {
            $names[] = $op['func'];
            self::assertSame(1, substr_count($readme, '### ' . $op['func'] . "\n"));
            self::assertStringContainsString('$client->' . $op['func'] . '(', $readme);
            if ($op['operationId'] === 'addPlaylistItem') {
                self::assertCount(1, array_intersect(['episode_id', 'podcast_id'], array_keys($op['example_params'])));
            }
        }
        $actual = get_class_methods(ApiMethods::class);
        sort($names); sort($actual);
        self::assertSame($names, $actual);
        self::assertSame(self::contract()['version'], Client::VERSION);
        preg_match_all('/```php\n(.*?)\n```/s', $readme, $matches);
        foreach ($matches[1] as $code) {
            // TOKEN_PARSE validates PHP syntax without executing examples or requests.
            token_get_all(str_starts_with(trim($code), '<?php') ? $code : '<?php ' . $code, TOKEN_PARSE);
        }
    }
}
