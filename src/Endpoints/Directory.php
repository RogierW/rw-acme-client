<?php

namespace Rogierw\RwAcme\Endpoints;

class Directory extends Endpoint
{
    public function all()
    {
        return $this->client
            ->getHttpClient()
            ->get($this->client->getBaseUrl() . '/directory');
    }

    public function newNonce(): string
    {
        return $this->all()->getBody()['newNonce'];
    }

    public function newAccount(): string
    {
        return $this->all()->getBody()['newAccount'];
    }

    public function newOrder(): string
    {
        return $this->all()->getBody()['newOrder'];
    }

    public function getOrder(): string
    {
        $url = str_replace('new-order', 'order', $this->newOrder());

        return rtrim($url, '/') . '/';
    }

    public function revoke(): string
    {
        return $this->all()->getBody()['revokeCert'];
    }
}
