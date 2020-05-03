<?php

namespace Rogierw\RwAcme;

use Psr\Log\LoggerInterface;
use Rogierw\RwAcme\Endpoints\Account;
use Rogierw\RwAcme\Endpoints\Certificate;
use Rogierw\RwAcme\Endpoints\Directory;
use Rogierw\RwAcme\Endpoints\DomainValidation;
use Rogierw\RwAcme\Endpoints\Nonce;
use Rogierw\RwAcme\Endpoints\Order;
use Rogierw\RwAcme\Http\Client;
use Rogierw\RwAcme\Support\Str;

class Api
{
    const PRODUCTION_URL = 'https://acme-v02.api.letsencrypt.org';
    const STAGING_URL = 'https://acme-staging-v02.api.letsencrypt.org';

    /** @var string */
    private $accountEmail;
    /** @var string */
    private $accountKeysPath;
    /** @var string */
    private $baseUrl;
    /** @var LoggerInterface|null */
    private $logger;

    /** @var \Rogierw\RwAcme\Http\Client */
    private $httpClient;

    public function __construct(string $accountEmail, string $accountKeysPath, bool $staging = false, LoggerInterface $logger = null)
    {
        $this->accountEmail = $accountEmail;
        $this->accountKeysPath = $accountKeysPath;
        $this->baseUrl = $staging ? self::STAGING_URL : self::PRODUCTION_URL;
        $this->logger = $logger;

        $this->httpClient = new Client($this->baseUrl);
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

    public function getAccountEmail(): string
    {
        return $this->accountEmail;
    }

    public function getAccountKeysPath(): string
    {
        if (!Str::endsWith($this->accountKeysPath, '/')) {
            $this->accountKeysPath .= '/';
        }

        if (!is_dir($this->accountKeysPath)) {
            mkdir($this->accountKeysPath, 0755, true);
        }

        return $this->accountKeysPath;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getHttpClient(): Client
    {
        return $this->httpClient;
    }

    public function logger(string $level, string $message): void
    {
        if ($this->logger instanceof LoggerInterface) {
            $this->logger->log($level, $message);
        }
    }
}
