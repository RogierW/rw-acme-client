<?php

namespace Rogierw\RwAcme\Endpoints;

use Rogierw\RwAcme\DTO\CertificateBundleData;
use Rogierw\RwAcme\DTO\OrderData;
use Rogierw\RwAcme\Support\Base64;
use RuntimeException;

class Certificate extends Endpoint
{
    public function getBundle(OrderData $orderData): CertificateBundleData
    {
        $signedPayload = $this->createKeyId($orderData->accountUrl, $orderData->certificateUrl);

        $response = $this->client->getHttpClient()->post($orderData->certificateUrl, $signedPayload);

        if ($response->getHttpResponseCode() !== 200) {
            throw new RuntimeException('Failed to fetch certificate.');
        }

        return CertificateBundleData::fromResponse($response);
    }

    public function revoke(string $pem, int $reason = 0): bool
    {
        if (($data = openssl_x509_read($pem)) === false) {
            throw new RuntimeException('Could not parse the certificate.');
        }

        if (openssl_x509_export($data, $certificate) === false) {
            throw new RuntimeException('Could not export the certificate.');
        }

        preg_match('~-----BEGIN\sCERTIFICATE-----(.*)-----END\sCERTIFICATE-----~s', $certificate, $matches);
        $certificate = trim(Base64::urlSafeEncode(base64_decode(trim($matches[1]))));

        $revokeUrl = $this->client->directory()->revoke();

        $signedPayload = $this->createKeyId(
            $this->client->account()->get()->url,
            $revokeUrl,
            [
                'certificate' => $certificate,
                'reason'      => $reason,
            ]
        );

        $response = $this->client->getHttpClient()->post($revokeUrl, $signedPayload);

        if ($response->getHttpResponseCode() === 200) {
            return true;
        }

        return false;
    }
}
