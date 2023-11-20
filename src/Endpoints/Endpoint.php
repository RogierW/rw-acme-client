<?php

namespace Rogierw\RwAcme\Endpoints;

use Rogierw\RwAcme\Api;
use Rogierw\RwAcme\Http\Response;
use Rogierw\RwAcme\Support\KeyId;

abstract class Endpoint
{
    public function __construct(protected Api $client)
    {
    }

    protected function createKeyId(string $accountUrl, string $url, ?array $payload = null): array
    {
        return KeyId::generate(
            $this->client->localAccount()->getPrivateKey(),
            $accountUrl,
            $url,
            $this->client->nonce()->getNew(),
            $payload
        );
    }

    protected function getAccountPrivateKey(): string
    {
        return $this->client->localAccount()->getPrivateKey();
    }

    protected function logResponse(string $level, string $message, Response $response, array $additionalContext = []): void
    {
        $this->client->logger($level, $message, array_merge([
            'url' => $response->getRequestedUrl(),
            'status' => $response->getHttpResponseCode(),
            'headers' => $response->getHeaders(),
            'body' => $response->getBody(),
        ], $additionalContext));
    }
}
