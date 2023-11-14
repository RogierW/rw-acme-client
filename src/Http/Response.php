<?php

namespace Rogierw\RwAcme\Http;

class Response
{
    public function __construct(
        private readonly array        $headers,
        private readonly string       $requestedUrl,
        private readonly ?int         $statusCode,
        private readonly array|string $body,
    )
    {
    }

    public function getHeader(string $name, $default = null): mixed
    {
        return $this->headers[$name] ?? $default;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader(string $name): bool
    {
        return isset($this->headers[$name]);
    }

    public function getBody(): array|string
    {
        return $this->body;
    }

    public function getRequestedUrl(): string
    {
        return $this->requestedUrl;
    }

    public function hasBody(): bool
    {
        return !empty($this->body);
    }

    public function getHttpResponseCode(): ?int
    {
        return $this->statusCode;
    }
}
