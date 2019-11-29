<?php

namespace Rogierw\Letsencrypt\Endpoints;

use Rogierw\Letsencrypt\DTO\AccountData;
use Rogierw\Letsencrypt\DTO\DomainValidationData;
use Rogierw\Letsencrypt\DTO\OrderData;
use Rogierw\Letsencrypt\Support\Base64;

class DomainValidation extends Endpoint
{
    const TYPE_HTTP = 'http-01';
    const TYPE_DNS = 'dns-01';

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
        $digest = $this->createDigest();

        return [
            'type'       => 'http',
            'identifier' => $domainValidation->identifier['value'],
            'filename'   => $domainValidation->file['token'],
            'content'    => $domainValidation->file['token'] . '.' . $digest,
        ];
    }

    public function start(AccountData $accountData, DomainValidationData $domainValidation)
    {
        $digest = $this->createDigest();

        $payload = [
            'keyAuthorization' => $domainValidation->file['token'] . '.' . $digest,
        ];

        $data = $this->createKeyId($accountData->url, $domainValidation->file['url'], $payload);

        return $this->client->getHttpClient()->post($domainValidation->file['url'], $data);
    }

    private function createDigest(): string
    {
        $privateKeyContent = file_get_contents($this->client->getAccountKeysPath() . 'private.pem');
        $privateKey = openssl_pkey_get_private($privateKeyContent);

        $details = openssl_pkey_get_details($privateKey);

        $header = [
            'e'   => Base64::urlSafeEncode($details['rsa']['e']),
            'kty' => 'RSA',
            'n'   => Base64::urlSafeEncode($details['rsa']['n']),
        ];

        return Base64::urlSafeEncode(hash('sha256', json_encode($header), true));
    }
}
