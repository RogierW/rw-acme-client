<?php

namespace Rogierw\RwAcme\Endpoints;

use Rogierw\RwAcme\DTO\AccountData;
use Rogierw\RwAcme\DTO\DomainValidationData;
use Rogierw\RwAcme\DTO\OrderData;
use Rogierw\RwAcme\Enums\AuthorizationChallengeEnum;
use Rogierw\RwAcme\Http\Response;
use Rogierw\RwAcme\Support\Arr;
use Rogierw\RwAcme\Support\DnsDigest;
use Rogierw\RwAcme\Support\JsonWebKey;
use Rogierw\RwAcme\Support\LocalChallengeTest;
use Rogierw\RwAcme\Support\Thumbprint;

class DomainValidation extends Endpoint
{
    /** @return DomainValidationData[] */
    public function status(OrderData $orderData): array
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
    public function getValidationData(array $challenges, ?AuthorizationChallengeEnum $authChallenge = null): array
    {
        $thumbprint = Thumbprint::make($this->getAccountPrivateKey());

        $authorizations = [];
        foreach ($challenges as $domainValidationData) {
            if ((is_null($authChallenge) || $authChallenge === AuthorizationChallengeEnum::HTTP)) {
                $authorizations[] = [
                    'identifier' => $domainValidationData->identifier['value'],
                    'type' => $domainValidationData->file['type'],
                    'filename' => $domainValidationData->file['token'],
                    'content' => $domainValidationData->file['token'] . '.' . $thumbprint,
                ];
            }

            if ((is_null($authChallenge) || $authChallenge === AuthorizationChallengeEnum::DNS)) {
                $authorizations[] = [
                    'identifier' => $domainValidationData->identifier['value'],
                    'type' => $domainValidationData->dns['type'],
                    'name' => '_acme-challenge',
                    'value' => DnsDigest::make($domainValidationData->dns['token'], $thumbprint),
                ];
            }
        }

        return $authorizations;
    }

    /** @throws \Rogierw\RwAcme\Exceptions\DomainValidationException */
    public function start(
        AccountData $accountData,
        DomainValidationData $domainValidation,
        AuthorizationChallengeEnum $authChallenge,
        bool $localTest = true
    ): Response {
        $this->client->logger('info', sprintf(
            'Start %s challenge for %s',
            $authChallenge->value,
            Arr::get($domainValidation->identifier, 'value', '')
        ));

        $type = $authChallenge === AuthorizationChallengeEnum::DNS ? 'dns' : 'file';
        $thumbprint = JsonWebKey::thumbprint(JsonWebKey::compute($this->getAccountPrivateKey()));
        $keyAuthorization = $domainValidation->{$type}['token'] . '.' . $thumbprint;

        if ($localTest) {
            if ($authChallenge === AuthorizationChallengeEnum::HTTP) {
                LocalChallengeTest::http(
                    $domainValidation->identifier['value'],
                    $domainValidation->file['token'],
                    $keyAuthorization
                );
            }

            if ($authChallenge === AuthorizationChallengeEnum::DNS) {
                LocalChallengeTest::dns(
                    $domainValidation->identifier['value'],
                    '_acme-challenge',
                    DnsDigest::make($domainValidation->{$type}['token'], $thumbprint),
                );
            }
        }

        $payload = [
            'keyAuthorization' => $keyAuthorization,
        ];

        $data = $this->createKeyId($accountData->url, $domainValidation->{$type}['url'], $payload);

        return $this->client->getHttpClient()->post($domainValidation->{$type}['url'], $data);
    }

    public function allChallengesPassed(OrderData $orderData): bool
    {
        $count = 0;
        while (($status = $this->status($orderData)) && $count < 4) {
            if ($this->challengeSucceeded($status)) {
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
    private function challengeSucceeded(array $domainValidation): bool
    {
        // Verify if the challenges has been passed.
        foreach ($domainValidation as $status) {
            $this->client->logger(
                'info',
                "Check {$status->identifier['type']} challenge of {$status->identifier['value']}."
            );

            if (!$status->isValid()) {
                return false;
            }
        }

        $this->client->logger('info', 'Challenge has been passed.');

        return true;
    }
}
