<?php

namespace Rogierw\RwAcme;

use Psr\Log\LoggerInterface;
use Rogierw\RwAcme\Endpoints\Account;
use Rogierw\RwAcme\Endpoints\Certificate;
use Rogierw\RwAcme\Endpoints\Directory;
use Rogierw\RwAcme\Endpoints\DomainValidation;
use Rogierw\RwAcme\Endpoints\Nonce;
use Rogierw\RwAcme\Endpoints\Order;
use Rogierw\RwAcme\Exceptions\LetsEncryptClientException;
use Rogierw\RwAcme\Http\Client;
use Rogierw\RwAcme\Interfaces\AcmeAccountInterface;

class Api
{
    private const PRODUCTION_URL = 'https://acme-v02.api.letsencrypt.org';
    private const STAGING_URL = 'https://acme-staging-v02.api.letsencrypt.org';

    private string $baseUrl;
    private Client $httpClient;

    public function __construct(
        private ?AcmeAccountInterface $localAccount = null,
        bool                          $staging = false,
        private ?LoggerInterface      $logger = null
    )
    {
        $this->baseUrl = $staging ? self::STAGING_URL : self::PRODUCTION_URL;
        $this->httpClient = new Client();
    }

    public function setLocalAccount(AcmeAccountInterface $account): self
    {
        $this->localAccount = $account;

        return $this;
    }

    public function localAccount(): AcmeAccountInterface
    {
        if ($this->localAccount === null) {
            throw new LetsEncryptClientException('No account set.');
        }

        return $this->localAccount;
    }

    public function directory(): Directory
    {
        return new Directory($this);
    }

    public function nonce(): Nonce
    {
        return new Nonce($this);
    }

    public function account(): Account
    {
        return new Account($this);
    }

    public function order(): Order
    {
        return new Order($this);
    }

    public function domainValidation(): DomainValidation
    {
        return new DomainValidation($this);
    }

    public function certificate(): Certificate
    {
        return new Certificate($this);
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getHttpClient(): Client
    {
        return $this->httpClient;
    }

    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    public function logger(string $level, string $message): void
    {
        if ($this->logger instanceof LoggerInterface) {
            $this->logger->log($level, $message);
        }
    }
}
