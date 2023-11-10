<?php

namespace Rogierw\RwAcme\Endpoints;

use Rogierw\RwAcme\DTO\AccountData;
use Rogierw\RwAcme\Exceptions\LetsEncryptClientException;
use Rogierw\RwAcme\Support\JsonWebSignature;

class Account extends Endpoint
{
    public function exists(): bool
    {
        return $this->client->keyStorage->exists();
    }

    public function create(): AccountData
    {
        $this->client->keyStorage->generateNewKeys();

        $payload = [
            'contact'              => $this->buildContactPayload($this->client->getAccountEmail()),
            'termsOfServiceAgreed' => true,
        ];

        $newAccountUrl = $this->client->directory()->newAccount();

        $signedPayload = JsonWebSignature::generate(
            $payload,
            $newAccountUrl,
            $this->client->nonce()->getNew(),
            $this->client->keyStorage->getPrivateKey(),
        );

        $response = $this->client->getHttpClient()->post(
            $newAccountUrl,
            $signedPayload
        );

        if ($response->getHttpResponseCode() === 201 && array_key_exists('Location', $response->getRawHeaders())) {
            return AccountData::fromResponse($response);
        }

        throw new LetsEncryptClientException('Creating account failed.');
    }

    public function get(): AccountData
    {
        if (!$this->exists()) {
            throw new LetsEncryptClientException('Account keys not found.');
        }

        $payload = [
            'onlyReturnExisting' => true,
        ];

        $newAccountUrl = $this->client->directory()->newAccount();

        $signedPayload = JsonWebSignature::generate(
            $payload,
            $newAccountUrl,
            $this->client->nonce()->getNew(),
            $this->client->keyStorage->getPrivateKey(),
        );

        $response = $this->client->getHttpClient()->post($newAccountUrl, $signedPayload);

        if ($response->getHttpResponseCode() === 400) {
            throw new LetsEncryptClientException($response->getBody());
        }

        return AccountData::fromResponse($response);
    }

    private function buildContactPayload(string $email): array
    {
        return [
            'mailto:' . $email,
        ];
    }
}
