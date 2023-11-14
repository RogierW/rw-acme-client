<?php

namespace Rogierw\RwAcme\Endpoints;

class Nonce extends Endpoint
{
    public function getNew(): string
    {
        $response = $this->client
            ->getHttpClient()
            ->head($this->client->directory()->newNonce());

        return trim($response->getHeader('replay-nonce'));
    }
}
