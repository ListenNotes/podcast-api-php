<?php

declare(strict_types=1);

namespace ListenNotes\PodcastApi\Http;

use ListenNotes\PodcastApi\Client;
use ListenNotes\PodcastApi\Exception;

class Curl
{
    protected $_curl;
    protected $_objInfo;
    protected $_strHeader = '';
    protected $_strBody = '';
    protected $_strHost = 'https://listen-api-test.listennotes.com';
    protected $_strMethod = '';
    protected $_strUri = '';
    protected $_strRequestBody = '';
    protected $_strVersion = 'api/v2';
    protected $_arrRequestHeaders = [];
    protected $_strUserAgent = 'podcast-api-php ' . Client::VERSION;
    protected float $timeout;

    public function __construct($strApiKey = '', $timeout = 30)
    {
        if ((!is_int($timeout) && !is_float($timeout)) || !is_finite($timeout) || $timeout <= 0) {
            throw new \InvalidArgumentException('timeout must be a finite positive number of seconds');
        }
        $this->timeout = (float) $timeout;
        $this->_objInfo = (object) ['http_code' => 0, 'request_header' => ''];
        if ($strApiKey !== null && $strApiKey !== '') {
            $this->_strHost = 'https://listen-api.listennotes.com';
            $this->setRequestHeader('X-ListenAPI-Key', $strApiKey);
        }
    }

    protected function requestApi(string $method, string $path, array $queryNames, array $params): string
    {
        $path = preg_replace_callback('/\{([^}]+)\}/', function (array $match) use (&$params): string {
            $name = $match[1];
            $value = $params[$name] ?? null;
            if ((!is_string($value) && !is_int($value)) || (string) $value === '') {
                throw new Exception\InvalidRequestException('Missing required path parameter: ' . $name);
            }
            unset($params[$name]);
            return rawurlencode((string) $value);
        }, $path);
        $query = $body = [];
        foreach ($params as $name => $value) {
            if ($value === null) {
                continue;
            }
            if (in_array($method, ['GET', 'DELETE'], true) || in_array($name, $queryNames, true)) {
                $query[$name] = $value;
            } else {
                $body[$name] = $value;
            }
        }
        $url = $this->getAction(ltrim($path, '/'));
        if ($query !== []) {
            $url .= '?' . $this->encodeParameters($query);
        }
        return match ($method) {
            'GET' => $this->get($url),
            'DELETE' => $this->delete($url),
            'POST' => $this->post($url, $body),
            'PUT' => $this->put($url, $body),
            default => throw new \InvalidArgumentException('Unsupported HTTP method'),
        };
    }

    protected function encodeParameters(array $params): string
    {
        return http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    public function getRequestHeader($strHeader = '')
    {
        foreach ($this->_arrRequestHeaders as $name => $value) {
            if (strcasecmp($name, $strHeader) === 0) {
                return $value;
            }
        }
        return null;
    }

    public function getRequestHeaders($strHeader = '')
    {
        $headers = [];
        foreach ($this->_arrRequestHeaders as $name => $value) {
            $headers[] = $name . ': ' . $value;
        }
        return $headers;
    }

    public function setRequestHeader($strHeader, $strValue)
    {
        if (!is_string($strHeader) || !preg_match('/^[!#$%&\'*+.^_`|~0-9A-Za-z-]+$/D', $strHeader)
            || !is_string($strValue) || strpbrk($strValue, "\r\n\0") !== false) {
            throw new \InvalidArgumentException('Invalid request header');
        }
        foreach (array_keys($this->_arrRequestHeaders) as $name) {
            if (strcasecmp($name, $strHeader) === 0) {
                unset($this->_arrRequestHeaders[$name]);
            }
        }
        $this->_arrRequestHeaders[$strHeader] = $strValue;
    }

    public function getAction($strAction = '')
    {
        return $this->_strHost . '/' . $this->_strVersion . '/' . $strAction;
    }

    public function getStatusCode()
    {
        return (int) $this->_objInfo->http_code;
    }

    protected function parseHeaderBlock(string $raw): array
    {
        $headers = [];
        foreach (preg_split('/\r?\n/', $raw) as $line) {
            if (preg_match('/^HTTP\/\S+\s+\d+/', $line)) {
                // Keep the final response after proxy/100 Continue header blocks.
                $headers = [];
            } elseif (str_contains($line, ':')) {
                [$name, $value] = explode(':', $line, 2);
                $headers[strtolower(trim($name))] = trim($value);
            }
        }
        return $headers;
    }

    public function getHeaders()
    {
        return $this->parseHeaderBlock($this->_strHeader);
    }

    public function setMethod($strMethod) { $this->_strMethod = $strMethod; }
    public function getMethod() { return $this->_strMethod; }
    public function setUri($strUri) { $this->_strUri = $strUri; }
    public function getUri() { return $this->_strUri; }
    public function setRequestBody($strBody) { $this->_strRequestBody = $strBody; }
    public function getRequestBody() { return $this->_strRequestBody; }

    public function parseRequestHeaders()
    {
        $raw = $this->_objInfo->request_header ?? '';
        if (preg_match('/^(\S+) (\S+) HTTP\/\S+/', $raw, $match)) {
            $this->setMethod($match[1]);
            $this->setUri($match[2]);
        }
        $headerLines = strstr($raw, "\n");
        return $this->parseHeaderBlock($headerLines === false ? '' : $headerLines);
    }

    public function setResponse($strResponse)
    {
        $size = curl_getinfo($this->_curl, CURLINFO_HEADER_SIZE);
        $this->_objInfo = (object) curl_getinfo($this->_curl);
        $this->parseRequestHeaders();
        $this->_strHeader = substr($strResponse, 0, $size);
        $this->_strBody = substr($strResponse, $size);
    }

    public function get($strUrl) { return $this->sendRequest('GET', $strUrl); }
    public function delete($strUrl) { return $this->sendRequest('DELETE', $strUrl); }
    public function post($strUrl, $arrOptions) { return $this->sendRequest('POST', $strUrl, $this->encodeParameters($arrOptions)); }
    public function put($strUrl, $arrOptions) { return $this->sendRequest('PUT', $strUrl, $this->encodeParameters($arrOptions)); }

    protected function sendRequest(string $method, string $url, ?string $body = null): string
    {
        // A fresh handle prevents method/body/header leakage and stale-connection replays.
        $this->_curl = curl_init();
        $this->_objInfo = (object) ['http_code' => 0, 'request_header' => ''];
        $this->_strHeader = $this->_strBody = '';
        $this->setMethod($method);
        $this->setUri($url);
        $this->setRequestBody($body ?? '');
        $headers = $this->getRequestHeaders();
        if ($body !== null && $this->getRequestHeader('Content-Type') === null) {
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        }
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_ENCODING => '',
            CURLOPT_USERAGENT => $this->_strUserAgent,
            CURLOPT_CONNECTTIMEOUT_MS => (int) max(1, min(10000, ceil($this->timeout * 1000))),
            CURLOPT_TIMEOUT_MS => (int) max(1, ceil($this->timeout * 1000)),
            CURLINFO_HEADER_OUT => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => $method,
        ];
        if ($body !== null) {
            $options[CURLOPT_POSTFIELDS] = $body;
        }
        curl_setopt_array($this->_curl, $options);
        $response = curl_exec($this->_curl);
        if ($response === false) {
            throw new Exception\APIConnectionException('Could not connect to Listen API: ' . curl_error($this->_curl), 0, null, 0);
        }
        $this->setResponse($response);
        $this->_processStatusCode();
        return $this->_strBody;
    }

    protected function _processStatusCode()
    {
        $status = $this->getStatusCode();
        if ($status >= 200 && $status < 300) {
            return;
        }
        $class = match ($status) {
            400 => Exception\InvalidRequestException::class,
            401 => Exception\AuthenticationException::class,
            403 => Exception\PermissionDeniedException::class,
            404 => Exception\NotFoundException::class,
            429 => Exception\RateLimitException::class,
            default => Exception\ListenApiException::class,
        };
        $data = json_decode($this->_strBody, true);
        $detail = is_array($data) ? ($data['error'] ?? null) : null;
        $message = 'Listen API returned HTTP ' . $status;
        if (is_string($detail) && $detail !== '') {
            $message .= ': ' . $detail;
        }
        throw new $class($message, $status, null, $status, $this->_strBody, $this->getHeaders());
    }
}
