<?php

namespace Rogierw\RwAcme\Endpoints;

use Rogierw\RwAcme\DTO\AccountData;
use Rogierw\RwAcme\DTO\DomainValidationData;
use Rogierw\RwAcme\DTO\OrderData;
use Rogierw\RwAcme\Exceptions\DomainValidationException;
use Rogierw\RwAcme\Http\Response;
use Rogierw\RwAcme\Support\Arr;
use Rogierw\RwAcme\Support\JsonWebKey;
use Rogierw\RwAcme\Support\LocalChallengeTest;

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

    /** @param DomainValidationData[] $challenges */
    public function getFileValidationData(array $challenges): array
    {
        $thumbprint = JsonWebKey::thumbprint(JsonWebKey::compute($this->getAccountPrivateKey()));

        $authorizations = [];
        foreach ($challenges as $domainValidationData) {
            if ($domainValidationData->dns['status'] === 'pending') {
                $authorizations[] = [
                    'type' => self::TYPE_HTTP,
                    'identifier' => $domainValidationData->identifier['value'],
                    'filename' => $domainValidationData->file['token'],
                    'content' => $domainValidationData->file['token'] . '.' . $thumbprint,
                ];
            }
        }

        return $authorizations;
    }

    /** @throws \Rogierw\RwAcme\Exceptions\DomainValidationException */
    public function start(AccountData $accountData, DomainValidationData $domainValidation, bool $localTest = true): Response
    {
        $this->client->logger(
            'info',
            'Start HTTP challenge for ' . Arr::get($domainValidation->identifier, 'value', '')
        );

        $thumbprint = JsonWebKey::thumbprint(JsonWebKey::compute($this->getAccountPrivateKey()));
        $keyAuthorization = $domainValidation->file['token'] . '.' . $thumbprint;

        if ($localTest) {
            LocalChallengeTest::http(
                $domainValidation->identifier['value'],
                $domainValidation->file['token'],
                $keyAuthorization
            );
        }

        $payload = [
            'keyAuthorization' => $keyAuthorization,
        ];

        $data = $this->createKeyId($accountData->url, $domainValidation->file['url'], $payload);

        return $this->client->getHttpClient()->post($domainValidation->file['url'], $data);
    }

    public function challengeSucceeded(OrderData $orderData, string $challengeType): bool
    {
        if ($challengeType !== self::TYPE_HTTP) {
            throw DomainValidationException::invalidChallengeType($challengeType);
        }

        $count = 0;
        while (($status = $this->status($orderData)) && $count < 4) {
            if ($challengeType === self::TYPE_HTTP && $this->httpChallengeSucceeded($status)) {
                break;
            }

            if ($count === 3) {
                return false;
            }

            $this->client->logger('info', 'Challenge is not valid yet. Another attempt in 5 seconds.');

            sleep(5);

            $count++;
        }

        return true;
    }

    /** @param DomainValidationData[] $domainValidation */
    private function httpChallengeSucceeded(array $domainValidation): bool
    {
        // Verify if all HTTP challenges has been passed.
        foreach ($domainValidation as $status) {
            $this->client->logger('info', "Check HTTP challenge of {$status->identifier['value']}.");

            if (!$status->isValid()) {
                return false;
            }
        }

        $this->client->logger('info', 'HTTP challenge has been passed.');

        return true;
    }
}
