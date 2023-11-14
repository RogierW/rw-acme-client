<?php

namespace Rogierw\RwAcme\Endpoints;

use Rogierw\RwAcme\DTO\AccountData;
use Rogierw\RwAcme\Exceptions\LetsEncryptClientException;
use Rogierw\RwAcme\Http\Response;
use Rogierw\RwAcme\Support\JsonWebSignature;

class Account extends Endpoint
{
    public function exists(): bool
    {
        return $this->client->localAccount()->exists();
    }

    public function create(): AccountData
    {
        $this->client->localAccount()->generateNewKeys();

        $payload = [
            'contact' => ['mailto:'.$this->client->localAccount()->getEmailAddress()],
            'termsOfServiceAgreed' => true,
        ];

        $response = $this->postToAccountUrl($payload);

        if ($response->getHttpResponseCode() === 201 && $response->hasHeader('location')) {
            return AccountData::fromResponse($response);
        }

        $this->throwError($response, 'Creating account failed');
    }

    public function get(): AccountData
    {
        if (!$this->exists()) {
            throw new LetsEncryptClientException('Local account keys not found.');
        }

        // Use the newAccountUrl to get the account data based on the key.
        // See https://datatracker.ietf.org/doc/html/rfc8555#section-7.3.1
        $payload = ['onlyReturnExisting' => true];
        $response = $this->postToAccountUrl($payload);

        if ($response->getHttpResponseCode() === 200) {
            return AccountData::fromResponse($response);
        }

        $this->throwError($response, 'Retrieving account failed');
    }

    private function signPayload(array $payload): array
    {
        return JsonWebSignature::generate(
            $payload,
            $this->client->directory()->newAccount(),
            $this->client->nonce()->getNew(),
            $this->client->localAccount()->getPrivateKey(),
        );
    }

    private function postToAccountUrl(array $payload): Response
    {
        return $this->client->getHttpClient()->post(
            $this->client->directory()->newAccount(),
            $this->signPayload($payload)
        );
    }

    protected function throwError(Response $response, string $defaultMessage): never
    {
        $message = $response->getBody()['details'] ?? $defaultMessage;
        $this->client->logger('error', $message, ['response' => $response->getBody()]);
        throw new LetsEncryptClientException($message);
    }
}
