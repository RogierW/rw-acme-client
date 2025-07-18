<?php

namespace Rogierw\RwAcme\DTO;

use Rogierw\RwAcme\Http\Response;
use Rogierw\RwAcme\Support\Url;
use Spatie\LaravelData\Data;

class AccountData extends Data
{
    public function __construct(
        public string $id,
        public string $url,
        public array $key,
        public string $status,
        public string $agreement,
        public string $createdAt,
    ) {
    }

    public static function fromResponse(Response $response): AccountData
    {
        $url = trim($response->getHeader('location', ''));

        return new self(
            id: Url::extractId($url),
            url: $url,
            key: $response->getBody()['key'],
            status: $response->getBody()['status'],
            agreement: $response->getBody()['agreement'] ?? '',
            createdAt: $response->getBody()['createdAt']
        );
    }
}
