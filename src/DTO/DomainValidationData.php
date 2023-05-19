<?php

namespace Rogierw\RwAcme\DTO;

use Rogierw\RwAcme\Enums\AuthorizationChallengeEnum;
use Rogierw\RwAcme\Http\Response;
use Rogierw\RwAcme\Support\Arr;
use Spatie\LaravelData\Data;

class DomainValidationData extends Data
{
    public function __construct(
        public array $identifier,
        public string $status,
        public string $expires,
        public array $file,
        public array $dns,
        public array $validationRecord,
    ) {
    }

    public static function fromResponse(Response $response): DomainValidationData
    {
        return new self(
            identifier: $response->getBody()['identifier'],
            status: $response->getBody()['status'],
            expires: $response->getBody()['expires'],
            file: self::getValidationByType($response->getBody()['challenges'], AuthorizationChallengeEnum::HTTP),
            dns: self::getValidationByType($response->getBody()['challenges'], AuthorizationChallengeEnum::DNS),
            validationRecord: Arr::get($response->getBody(), 'validationRecord', []),
        );
    }

    private static function getValidationByType(array $haystack, AuthorizationChallengeEnum $authChallenge): array
    {
        foreach ($haystack as $key => $data) {
            if ($data['type'] === $authChallenge->value) {
                return $data;
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
                'domainValidationType' => AuthorizationChallengeEnum::HTTP->value,
                'error' => Arr::get($this->file, 'error'),
            ];

            $data[] = [
                'domainValidationType' => AuthorizationChallengeEnum::DNS->value,
                'error' => Arr::get($this->dns, 'error'),
            ];

            return $data;
        }

        return [];
    }
}
