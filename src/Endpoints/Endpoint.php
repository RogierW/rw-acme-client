<?php

namespace Rogierw\RwAcme\Endpoints;

use Rogierw\RwAcme\Api;
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
}
