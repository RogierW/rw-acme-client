<?php

namespace Rogierw\RwAcme\DTO;

use Rogierw\RwAcme\Http\Response;
use Rogierw\RwAcme\Support\Arr;
use Rogierw\RwAcme\Support\Url;
use Spatie\DataTransferObject\DataTransferObject;

class AccountData extends DataTransferObject
{
    public $id;
    public $url;
    public $key;
    public $status;
    public $contact;
    public $agreement;
    public $initialIp;
    public $createdAt;

    public static function fromResponse(Response $response): self
    {
        $url = trim(Arr::get($response->getRawHeaders(), 'Location', ''));

        return new self([
            'id'        => Url::extractId($url),
            'url'       => $url,
            'key'       => $response->getBody()['key'],
            'status'    => $response->getBody()['status'],
            'contact'   => $response->getBody()['contact'],
            'agreement' => $response->getBody()['agreement'] ?? '',
            'initialIp' => $response->getBody()['initialIp'],
            'createdAt' => $response->getBody()['createdAt'],
        ]);
    }
}
