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
use Rogierw\RwAcme\Interfaces\KeyStorageInterface;
use Rogierw\RwAcme\Support\KeyStorage\FileKeyStorage;

class Api
{
    private const PRODUCTION_URL = 'https://acme-v02.api.letsencrypt.org';
    private const STAGING_URL = 'https://acme-staging-v02.api.letsencrypt.org';

    private string $baseUrl;
    private Client $httpClient;
    public KeyStorageInterface $keyStorage;

    public function __construct(
        KeyStorageInterface|string $keyStorage,
        private readonly ?string   $accountEmail = null,
        bool                       $staging = false,
        private ?LoggerInterface   $logger = null
    )
    {
        $this->baseUrl = $staging ? self::STAGING_URL : self::PRODUCTION_URL;
        $this->httpClient = new Client();

        // If a string is passed, create a FileKeyStorage instance with the string as the path.
        if (is_string($keyStorage)) {
            $this->keyStorage = new FileKeyStorage($keyStorage);
        } else {
            $this->keyStorage = $keyStorage;
        }

        if ($this->accountEmail !== null) {
            $this->useAccount($this->accountEmail);
        }
    }

    public function useAccount(string $accountName): self
    {
        $alphaNumAccountName = preg_replace('/[^a-zA-Z0-9\-]/', '_', $accountName);
        $shortHash = substr(hash('sha256', $accountName), 0, 16);
        // Set/change the account name to allow for multiple accounts to be used.
        $this->keyStorage->setAccountName($shortHash.'_'.$alphaNumAccountName);

        return $this;
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
