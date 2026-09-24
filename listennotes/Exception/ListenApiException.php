<?php

declare(strict_types=1);

namespace ListenNotes\PodcastApi\Exception;

class ListenApiException extends \Exception
{
    public const STATUS = 0;
    private int $status;
    private string $responseBody;
    private array $responseHeaders;

    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null,
        ?int $status = null, string $responseBody = '', array $responseHeaders = [])
    {
        parent::__construct($message, $code, $previous);
        $this->status = $status ?? static::STATUS;
        $this->responseBody = $responseBody;
        $this->responseHeaders = $responseHeaders;
    }

    public function getStatus(): int { return $this->status; }
    public function getResponseBody(): string { return $this->responseBody; }
    public function getResponseHeaders(): array { return $this->responseHeaders; }
}
