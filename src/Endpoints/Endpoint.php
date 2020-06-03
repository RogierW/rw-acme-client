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

    protected function createKeyId(string $acountUrl, string $url, $payload = null): array
    {
        return KeyId::generate(
            $payload,
            $acountUrl,
            $url,
            $this->client->nonce()->getNew(),
            $this->client->getAccountKeysPath()
        );
    }

    protected function getAccountPrivateKey(): string
    {
        return file_get_contents($this->client->getAccountKeysPath() . 'private.pem');
    }
}
