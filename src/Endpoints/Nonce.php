<?php

namespace Rogierw\RwAcme\Endpoints;

class Nonce extends Endpoint
{
    public function getNew()
    {
        $response = $this->client
            ->getHttpClient()
            ->head($this->client->directory()->newNonce());

        return trim($response->getRawHeaders()['Replay-Nonce']);
    }
}
