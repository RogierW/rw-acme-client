<?php

namespace Rogierw\RwAcme\Endpoints;

use Rogierw\RwAcme\DTO\AccountData;
use Rogierw\RwAcme\DTO\OrderData;
use Rogierw\RwAcme\Support\Base64;
use RuntimeException;

class Order extends Endpoint
{
    public function new(AccountData $accountData, array $domains): OrderData
    {
        $identifiers = [];
        foreach ($domains as $domain) {
            if (preg_match_all('~(\*\.)~', $domain) > 1) {
                throw new RuntimeException('Cannot create orders with multiple wildcards in one domain.');
            }

            $identifiers[] = [
                'type'  => 'dns',
                'value' => $domain,
            ];
        }

        $payload = [
            'identifiers' => $identifiers,
            'notBefore'   => '',
            'notAfter'    => '',
        ];

        $newOrderUrl = $this->client->directory()->newOrder();

        $keyId = $this->createKeyId(
            $accountData->url,
            $this->client->directory()->newOrder(),
            $payload
        );

        $response = $this->client->getHttpClient()->post($newOrderUrl, $keyId);

        if ($response->getHttpResponseCode() !== 201) {
            throw new RuntimeException('Creating new order failed; bad response code.');
        }

        return OrderData::fromResponse($response, $accountData->url);
    }

    public function get(string $id): OrderData
    {
        $account = $this->client->account()->get();

        $orderUrl = vsprintf('%s%s/%s', [
            $this->client->directory()->getOrder(),
            $account->id,
            $id,
        ]);

        $response = $this->client->getHttpClient()->get($orderUrl);

        return OrderData::fromResponse($response, $account->url);
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
            $orderData->setCertificateUrl($response->getBody()['certificate']);

            return true;
        }

        $this->client->logger('error', 'Finalize order: ' . json_encode($response->getBody()));

        return false;
    }
}
