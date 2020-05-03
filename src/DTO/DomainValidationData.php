<?php

namespace Rogierw\RwAcme\DTO;

use Rogierw\RwAcme\Endpoints\DomainValidation;
use Rogierw\RwAcme\Http\Response;
use Rogierw\RwAcme\Support\Arr;
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
            'identifier' => $response->getBody()['identifier'],
            'status' => $response->getBody()['status'],
            'expires' => $response->getBody()['expires'],
            'file' => self::getValidationByType($response->getBody()['challenges'], DomainValidation::TYPE_HTTP),
            'dns' => self::getValidationByType($response->getBody()['challenges'], DomainValidation::TYPE_DNS),
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

    public function isInvalid(): bool
    {
        return $this->status === 'invalid';
    }

    public function hasErrors(): bool
    {
        if (array_key_exists('error', $this->file) && !empty($this->file['error'])) {
            return true;
        }

        if (array_key_exists('error', $this->dns) && !empty($this->dns['error'])) {
            return true;
        }

        return false;
    }

    public function getErrors(): array
    {
        if ($this->hasErrors()) {
            $data = [];

            $data[] = [
                'domainValidationType' => DomainValidation::TYPE_HTTP,
                'error' => Arr::get($this->file, 'error'),
            ];

            $data[] = [
                'domainValidationType' => DomainValidation::TYPE_DNS,
                'error' => Arr::get($this->dns, 'error'),
            ];

            return $data;
        }

        return [];
    }
}
