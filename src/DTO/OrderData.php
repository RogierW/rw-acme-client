<?php

namespace Rogierw\RwAcme\DTO;

use Rogierw\RwAcme\Http\Response;
use Rogierw\RwAcme\Support\Arr;
use Rogierw\RwAcme\Support\Url;
use Spatie\LaravelData\Data;

class OrderData extends Data
{
    public function __construct(
        public string $id,
        public string $url,
        public string $status,
        public string $expires,
        public array $identifiers,
        public array $domainValidationUrls,
        public string $finalizeUrl,
        public string $accountUrl,
        public string|null $certificateUrl,
        public bool $finalized = false,
    ) {}

    public static function fromResponse(Response $response, string $accountUrl = ''): OrderData
    {
        $url = Arr::get($response->getRawHeaders(), 'Location');

        if (empty($url)) {
            $url = Arr::get($response->getHeaders(), 'url');
        }

        $url = trim(rtrim($url, '?'));

        return new self(
            id: Url::extractId($url),
            url: $url,
            status: $response->getBody()['status'],
            expires: $response->getBody()['expires'],
            identifiers: $response->getBody()['identifiers'],
            domainValidationUrls: $response->getBody()['authorizations'],
            finalizeUrl: $response->getBody()['finalize'],
            accountUrl: $accountUrl,
            certificateUrl: Arr::get($response->getBody(), 'certificate'),
        );
    }

    public function setCertificateUrl(string $url): void
    {
        $this->certificateUrl = $url;
        $this->finalized = true;
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isReady(): bool
    {
        return $this->status === 'ready';
    }

    public function isValid(): bool
    {
        return $this->status === 'valid';
    }

    public function isInvalid(): bool
    {
        return $this->status === 'invalid';
    }

    public function isFinalized(): bool
    {
        return ($this->finalized || $this->isValid());
    }

    public function isNotFinalized(): bool
    {
        return !$this->isFinalized();
    }
}
