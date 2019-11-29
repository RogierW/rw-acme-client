<?php

namespace Rogierw\Letsencrypt\Endpoints;

use Rogierw\Letsencrypt\Api;
use Rogierw\Letsencrypt\Support\KeyId;

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
}
