<?php

namespace Rogierw\RwAcme\Endpoints;

use Rogierw\RwAcme\DTO\AccountData;
use Rogierw\RwAcme\DTO\OrderData;
use Rogierw\RwAcme\Exceptions\LetsEncryptClientException;
use Rogierw\RwAcme\Exceptions\OrderNotFoundException;
use Rogierw\RwAcme\Exceptions\RateLimitException;
use Rogierw\RwAcme\Support\Base64;

class Order extends Endpoint
{
    public function new(AccountData $accountData, array $domains): OrderData
    {
        $identifiers = [];
        foreach ($domains as $domain) {
            if (preg_match_all('~(\*\.)~', $domain) > 1) {
                throw new LetsEncryptClientException('Cannot create orders with multiple wildcards in one domain.');
            }

            $identifiers[] = [
                'type' => 'dns',
                'value' => $domain,
            ];
        }

        $payload = [
            'identifiers' => $identifiers,
            'notBefore' => '',
            'notAfter' => '',
        ];

        $newOrderUrl = $this->client->directory()->newOrder();

        $keyId = $this->createKeyId(
            $accountData->url,
            $this->client->directory()->newOrder(),
            $payload
        );

        $response = $this->client->getHttpClient()->post($newOrderUrl, $keyId);

        if ($response->getHttpResponseCode() === 201) {
            return OrderData::fromResponse($response, $accountData->url);
        }

        $this->logResponse('error', 'Creating new order failed; bad response code.', $response, ['payload' => $payload]);
        throw new LetsEncryptClientException('Creating new order failed; bad response code.');
    }

    public function get(string $id): OrderData
    {
        $account = $this->client->account()->get();

        $orderUrl = sprintf('%s%s/%s',
            $this->client->directory()->getOrder(),
            $account->id,
            $id,
        );

        $response = $this->client->getHttpClient()->get($orderUrl);

        // Everything below 400 is a success.
        if ($response->getHttpResponseCode() < 400) {
            return OrderData::fromResponse($response, $account->url);
        }

        // Always log the error.
        $this->logResponse('error', 'Getting order failed; bad response code.', $response);

        match ($response->getHttpResponseCode()) {
            404 => throw new OrderNotFoundException($response->getBody()['detail'] ?? 'Order cannot be found.'),
            429 => throw new RateLimitException($response->getBody()['detail'] ?? 'Too many requests.'),
            default => throw new LetsEncryptClientException($response->getBody()['detail'] ?? 'Unknown error.'),
        };
    }

    public function finalize(OrderData $orderData, string $csr): bool
    {
        if (!$orderData->isReady()) {
            $this->client->logger(
                'error',
                "Order status for {$orderData->id} is {$orderData->status}. Cannot finalize order."
            );

            return false;
        }

        if (preg_match('~-----BEGIN\sCERTIFICATE\sREQUEST-----(.*)-----END\sCERTIFICATE\sREQUEST-----~s', $csr, $matches)) {
            $csr = $matches[1];
        }

        $csr = trim(Base64::urlSafeEncode(base64_decode($csr)));

        $signedPayload = $this->createKeyId(
            $orderData->accountUrl,
            $orderData->finalizeUrl,
            compact('csr')
        );

        $response = $this->client->getHttpClient()->post($orderData->finalizeUrl, $signedPayload);

        if ($response->getHttpResponseCode() === 200) {
            $body = $response->getBody();

            if (isset($body['certificate'])) {
                $orderData->setCertificateUrl($body['certificate']);
            }

            return true;
        }

        $this->logResponse('error', 'Cannot finalize order '.$orderData->id, $response, ['orderData' => $orderData]);

        return false;
    }
}
