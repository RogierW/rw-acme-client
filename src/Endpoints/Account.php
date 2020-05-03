<?php

namespace Rogierw\RwAcme\Endpoints;

use Rogierw\RwAcme\DTO\AccountData;
use Rogierw\RwAcme\Support\CryptRSA;
use Rogierw\RwAcme\Support\JsonWebSignature;
use RuntimeException;

class Account extends Endpoint
{
    public function exists(): bool
    {
        if (!is_dir($this->client->getAccountKeysPath())) {
            return false;
        }

        if (is_file($this->client->getAccountKeysPath() . 'private.pem')
            && is_file($this->client->getAccountKeysPath() . 'public.pem')) {
            return true;
        }

        return false;
    }

    public function create(): AccountData
    {
        $this->initAccountDirectory();

        $payload = [
            'contact'              => $this->buildContactPayload($this->client->getAccountEmail()),
            'termsOfServiceAgreed' => true,
        ];

        $newAccountUrl = $this->client->directory()->newAccount();

        $signedPayload = JsonWebSignature::generate(
            $payload,
            $newAccountUrl,
            $this->client->nonce()->getNew(),
            $this->client->getAccountKeysPath()
        );

        $response = $this->client->getHttpClient()->post(
            $newAccountUrl,
            $signedPayload
        );

        if ($response->getHttpResponseCode() === 201 && array_key_exists('Location', $response->getRawHeaders())) {
            return AccountData::fromResponse($response);
        }

        throw new RuntimeException('Creating account failed.');
    }

    public function get(): AccountData
    {
        if (!$this->exists()) {
            throw new RuntimeException('Account keys not found.');
        }

        $payload = [
            'onlyReturnExisting' => true,
        ];

        $newAccountUrl = $this->client->directory()->newAccount();

        $signedPayload = JsonWebSignature::generate(
            $payload,
            $newAccountUrl,
            $this->client->nonce()->getNew(),
            $this->client->getAccountKeysPath()
        );

        $response = $this->client->getHttpClient()->post($newAccountUrl, $signedPayload);

        return AccountData::fromResponse($response);
    }

    private function initAccountDirectory(string $keyType = 'RSA'): void
    {
        if ($keyType !== 'RSA') {
            throw new RuntimeException('Key type is not supported.');
        }

        if (!is_dir($this->client->getAccountKeysPath())) {
            mkdir($this->client->getAccountKeysPath());
        }

        if ($keyType === 'RSA') {
            CryptRSA::generate($this->client->getAccountKeysPath());
        }
    }

    private function buildContactPayload(string $email): array
    {
        return [
            'mailto:' . $email,
        ];
    }
}
