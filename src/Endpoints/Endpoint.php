<?php

namespace Rogierw\RwAcme\Endpoints;

use Rogierw\RwAcme\Api;
use Rogierw\RwAcme\Support\KeyId;

abstract class Endpoint
{
    public function __construct(protected Api $client)
    {
    }

    protected function createKeyId(string $acountUrl, string $url, ?array $payload = null): array
    {
        return KeyId::generate(
            $this->client->getAccountKeysPath(),
            $acountUrl,
            $url,
            $this->client->nonce()->getNew(),
            $payload
        );
    }

    protected function getAccountPrivateKey(): string
    {
        return file_get_contents($this->client->getAccountKeysPath() . 'private.pem');
    }
}
