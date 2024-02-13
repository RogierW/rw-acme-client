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
use Rogierw\RwAcme\Interfaces\HttpClientInterface;

class Api
{
    private const PRODUCTION_URL = 'https://acme-v02.api.letsencrypt.org';
    private const STAGING_URL = 'https://acme-staging-v02.api.letsencrypt.org';

    public function __construct(
        bool $staging = false,
        private ?AcmeAccountInterface $localAccount = null,
        private ?LoggerInterface $logger = null,
        private HttpClientInterface|null $httpClient = null,
        string $customUrl = ''
    ) {
        if ($staging) {
            $this->baseUrl = empty($customUrl) ? (self::STAGING_URL) : $customUrl;
        } else {
            $this->baseUrl = empty($customUrl) ? (self::PRODUCTION_URL) : $customUrl;
        }
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

    public function getHttpClient(): HttpClientInterface
    {
        // Create a default client if none is set.
        if ($this->httpClient === null) {
            $this->httpClient = new Client();
        }

        return $this->httpClient;
    }

    public function setHttpClient(HttpClientInterface $httpClient): self
    {
        $this->httpClient = $httpClient;

        return $this;
    }

    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    public function logger(string $level, string $message, array $context = []): void
    {
        if ($this->logger instanceof LoggerInterface) {
            $this->logger->log($level, $message, $context);
        }
    }
}
