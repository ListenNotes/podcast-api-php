<?php

declare(strict_types=1);

namespace ListenNotes\PodcastApi\Tests\Integration;

use ListenNotes\PodcastApi\{ApiMethods, Http\Curl, Exception\NotFoundException};
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MockOnlyClient extends Curl
{
    use ApiMethods;

    public function __construct() { parent::__construct(null, 15); }

    protected function sendRequest(string $method, string $url, ?string $body = null): string
    {
        $uri = parse_url($url);
        if (($uri['scheme'] ?? '') !== 'https' || ($uri['host'] ?? '') !== 'listen-api-test.listennotes.com'
            || ($uri['port'] ?? 443) !== 443 || isset($uri['user']) || isset($uri['pass'])
            || !str_starts_with($uri['path'] ?? '', '/api/v2/')) {
            throw new \LogicException('Integration tests may only contact the public mock API');
        }
        foreach (['Authorization', 'Proxy-Authorization', 'X-ListenAPI-Key'] as $name) {
            if ($this->getRequestHeader($name) !== null) { throw new \LogicException('Integration requests must not include credentials'); }
        }
        return parent::sendRequest($method, $url, $body);
    }
}

final class MockApiTest extends TestCase
{
    private MockOnlyClient $client;
    private static array $proxies;

    public static function setUpBeforeClass(): void
    {
        self::$proxies = [];
        foreach (['http_proxy', 'https_proxy', 'all_proxy', 'HTTP_PROXY', 'HTTPS_PROXY', 'ALL_PROXY'] as $name) {
            self::$proxies[$name] = getenv($name);
            putenv($name);
        }
    }

    public static function tearDownAfterClass(): void
    {
        foreach (self::$proxies as $name => $value) { putenv($value === false ? $name : "$name=$value"); }
    }

    protected function setUp(): void { $this->client = new MockOnlyClient(); }

    public static function operations(): iterable
    {
        $contract = json_decode(file_get_contents(dirname(__DIR__, 2) . '/listennotes/api-contract.json'), true, 512, JSON_THROW_ON_ERROR);
        foreach ($contract['operations'] as $op) { yield $op['func'] => [$op]; }
    }

    private function checkResponse(string $response, string $method, string $path, int $status = 200): array
    {
        self::assertSame($status, $this->client->getStatusCode(), substr($response, 0, 500));
        self::assertSame($method, $this->client->getMethod());
        self::assertSame('/api/v2' . $path, parse_url($this->client->getUri(), PHP_URL_PATH));
        $headers = $this->client->getHeaders();
        self::assertStringStartsWith('application/json', $headers['content-type']);
        self::assertGreaterThanOrEqual(0, (int) $headers['x-listenapi-usage']);
        self::assertGreaterThan(0, (int) $headers['x-listenapi-freequota']);
        self::assertGreaterThanOrEqual(0, (float) $headers['x-listenapi-latency-seconds']);
        self::assertNotEmpty($headers['x-listenapi-nextbillingdate']);
        $payload = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);
        return $payload;
    }

    #[DataProvider('operations')]
    public function testAllMethodsAgainstPublicMock(array $op): void
    {
        $params = $op['example_params'];
        $path = preg_replace_callback('/\{([^}]+)\}/', static function ($m) use (&$params) {
            $value = $params[$m[1]]; unset($params[$m[1]]); return rawurlencode((string) $value);
        }, $op['path']);
        $status = in_array($op['operationId'], ['createPlaylist', 'addPlaylistItem'], true) ? 201 : 200;
        $payload = $this->checkResponse($this->client->{$op['func']}($op['example_params']), $op['method'], $path, $status);
        if (in_array($op['method'], ['GET', 'DELETE'], true)) {
            parse_str(parse_url($this->client->getUri(), PHP_URL_QUERY) ?? '', $sent);
        } else { parse_str($this->client->getRequestBody(), $sent); }
        self::assertSame(array_map('strval', $params), $sent);
        if (in_array($op['operationId'], ['createPlaylist', 'updatePlaylist', 'getPlaylistById'], true)) {
            self::assertIsString($payload['id']);
            self::assertContains($payload['type'], ['episode_list', 'podcast_list']);
            self::assertContains($payload['visibility'], ['public', 'unlisted', 'private']);
            self::assertStringStartsWith('https://www.listennotes.com/', $payload['listennotes_url']);
        } elseif (in_array($op['operationId'], ['addPlaylistItem', 'updatePlaylistItemNotes'], true)) {
            self::assertIsInt($payload['id']); self::assertIsString($payload['notes']);
            self::assertIsArray($payload['data']); self::assertContains($payload['type'], ['episode', 'podcast']);
        } elseif ($op['operationId'] === 'deletePlaylistItem') {
            self::assertTrue($payload['deleted']); self::assertIsInt($payload['id']);
        }
    }

    public function testEncodedSearch(): void
    {
        $this->checkResponse($this->client->search(['q' => 'science & café', 'offset' => 0]), 'GET', '/search');
        parse_str(parse_url($this->client->getUri(), PHP_URL_QUERY), $query);
        self::assertSame(['q' => 'science & café', 'offset' => '0'], $query);
    }

    public function testPodcastAddition(): void
    {
        $this->checkResponse($this->client->addPlaylistItem(['id' => 'm1pe7z60bsw',
            'podcast_id' => '4d3fe717742d4963a85562e9f84d8c79', 'notes' => 'hello & café']), 'POST', '/playlists/m1pe7z60bsw/items', 201);
        parse_str($this->client->getRequestBody(), $body);
        self::assertSame(['podcast_id' => '4d3fe717742d4963a85562e9f84d8c79', 'notes' => 'hello & café'], $body);
    }

    public function testEmptyDescription(): void
    {
        $this->checkResponse($this->client->updatePlaylist(['id' => 'm1pe7z60bsw', 'description' => '']), 'PUT', '/playlists/m1pe7z60bsw');
        self::assertSame('description=', $this->client->getRequestBody());
    }

    public function testMissingRoute(): void
    {
        $this->expectException(NotFoundException::class);
        $this->client->get($this->client->getAction('sdk-integration-missing-route'));
    }
}
