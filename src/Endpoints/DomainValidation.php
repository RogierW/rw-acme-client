<?php

namespace Rogierw\RwAcme\Endpoints;

use Rogierw\RwAcme\DTO\AccountData;
use Rogierw\RwAcme\DTO\DomainValidationData;
use Rogierw\RwAcme\DTO\OrderData;
use Rogierw\RwAcme\Http\Response;
use Rogierw\RwAcme\Support\Arr;
use Rogierw\RwAcme\Support\JsonWebKey;

class DomainValidation extends Endpoint
{
    const TYPE_HTTP = 'http-01';
    const TYPE_DNS = 'dns-01';

    /** @return DomainValidationData[] */
    public function status(OrderData $orderData, string $type = 'all'): array
    {
        $data = [];

        foreach ($orderData->domainValidationUrls as $domainValidationUrl) {
            $response = $this->client
                ->getHttpClient()
                ->post(
                    $domainValidationUrl,
                    $this->createKeyId($orderData->accountUrl, $domainValidationUrl)
                );

            if ($response->getHttpResponseCode() === 200) {
                $data[] = DomainValidationData::fromResponse($response);
            }
        }

        return $data;
    }

    public function getFileValidationData(DomainValidationData $domainValidation): array
    {
        $thumbprint = JsonWebKey::thumbprint(JsonWebKey::compute($this->getAccountPrivateKey()));

        return [
            'type' => self::TYPE_HTTP,
            'identifier' => $domainValidation->identifier['value'],
            'filename' => $domainValidation->file['token'],
            'content' => $domainValidation->file['token'] . '.' . $thumbprint,
        ];
    }

    public function start(AccountData $accountData, DomainValidationData $domainValidation): Response
    {
        $this->client->logger(
            'info',
            'Start HTTP challenge for ' . Arr::get($domainValidation->identifier, 'value', '')
        );

        $thumbprint = JsonWebKey::thumbprint(JsonWebKey::compute($this->getAccountPrivateKey()));

        $payload = [
            'keyAuthorization' => $domainValidation->file['token'] . '.' . $thumbprint,
        ];

        $data = $this->createKeyId($accountData->url, $domainValidation->file['url'], $payload);

        return $this->client->getHttpClient()->post($domainValidation->file['url'], $data);
    }
}
