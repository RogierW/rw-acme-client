<?php

namespace Rogierw\Letsencrypt\DTO;

use Rogierw\Letsencrypt\Endpoints\DomainValidation;
use Rogierw\Letsencrypt\Http\Response;
use Rogierw\Letsencrypt\Support\Arr;
use Spatie\DataTransferObject\DataTransferObject;

class DomainValidationData extends DataTransferObject
{
    public $identifier;
    public $status;
    public $expires;
    public $file;
    public $dns;
    public $validationRecord;

    public static function fromResponse(Response $response): self
    {
        return new self([
            'identifier'       => $response->getBody()['identifier'],
            'status'           => $response->getBody()['status'],
            'expires'          => $response->getBody()['expires'],
            'file'             => self::getValidationByType($response->getBody()['challenges'], DomainValidation::TYPE_HTTP),
            'dns'              => self::getValidationByType($response->getBody()['challenges'], DomainValidation::TYPE_DNS),
            'validationRecord' => Arr::get($response->getBody(), 'validationRecord', []),
        ]);
    }

    private static function getValidationByType(array $haystack, string $type): array
    {
        foreach ($haystack as $key => $data) {
            if ($data['type'] === $type) {
                return $haystack[$key];
            }
        }

        return [];
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isValid(): bool
    {
        return $this->status === 'valid';
    }
}
