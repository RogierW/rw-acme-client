<?php

namespace Rogierw\RwAcme\Support;

use Rogierw\RwAcme\Exceptions\LetsEncryptClientException;

class JsonWebKey
{
    public static function compute(
        #[\SensitiveParameter] string $accountKey
    ): array
    {
        $privateKey = openssl_pkey_get_private($accountKey);

        if ($privateKey === false) {
            throw new LetsEncryptClientException('Can not create private key.');
        }

        $details = openssl_pkey_get_details($privateKey);

        return [
            'e' => Base64::urlSafeEncode($details['rsa']['e']),
            'kty' => 'RSA',
            'n' => Base64::urlSafeEncode($details['rsa']['n']),
        ];
    }

    public static function thumbprint(array $jwk): string
    {
        return Base64::urlSafeEncode(hash('sha256', json_encode($jwk, JSON_THROW_ON_ERROR), true));
    }
}
