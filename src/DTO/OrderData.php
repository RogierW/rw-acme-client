<?php

namespace Rogierw\RwAcme\DTO;

use Rogierw\RwAcme\Http\Response;
use Rogierw\RwAcme\Support\Arr;
use Rogierw\RwAcme\Support\Url;
use Spatie\DataTransferObject\DataTransferObject;

class OrderData extends DataTransferObject
{
    public $id;
    public $url;
    public $status;
    public $expires;
    public $identifiers;
    public $domainValidationUrls;
    public $finalizeUrl;
    public $accountUrl;
    public $certificateUrl;

    private $finalized = false;

    public static function fromResponse(Response $response, string $accountUrl = ''): self
    {
        $url = Arr::get($response->getRawHeaders(), 'Location');

        if (empty($url)) {
            $url = Arr::get($response->getHeaders(), 'url');
        }

        $url = trim(rtrim($url, '?'));

        return new self([
            'id'                   => Url::extractId($url),
            'url'                  => $url,
            'status'               => $response->getBody()['status'],
            'expires'              => $response->getBody()['expires'],
            'identifiers'          => $response->getBody()['identifiers'],
            'domainValidationUrls' => $response->getBody()['authorizations'],
            'finalizeUrl'          => $response->getBody()['finalize'],
            'certificateUrl'       => Arr::get($response->getBody(), 'certificate'),
            'accountUrl'           => $accountUrl,
        ]);
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
