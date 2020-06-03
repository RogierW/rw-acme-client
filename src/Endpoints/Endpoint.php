<?php

namespace Rogierw\RwAcme\Endpoints;

use Rogierw\RwAcme\Api;
use Rogierw\RwAcme\Support\KeyId;

abstract class Endpoint
{
    protected $client;

    public function __construct(Api $client)
    {
        $this->client = $client;
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
