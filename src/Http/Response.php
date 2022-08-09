<?php

namespace Rogierw\RwAcme\Http;

use Rogierw\RwAcme\Support\Str;

class Response
{
    public function __construct(
        private string $rawHeaders,
        private array $headers,
        private array|string $body,
        private string $error
    ) {
    }

    public function getRawHeaders(): array
    {
        $headers = explode("\n", $this->rawHeaders);
        $headersArr = [];

        foreach ($headers as $header) {
            if (!Str::contains($header, ':')) {
                continue;
            }

            [$name, $value] = explode(':', $header, 2);

            $headersArr[$name] = $value;
        }

        return $headersArr;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getBody(): array|string
    {
        return $this->body;
    }

    public function hasBody(): bool
    {
        return $this->body != false;
    }

    public function getError(): string
    {
        return $this->error;
    }

    public function getHttpResponseCode(): ?int
    {
        if (!isset($this->headers['http_code'])) {
            return null;
        }

        return (int) $this->headers['http_code'];
    }
}
