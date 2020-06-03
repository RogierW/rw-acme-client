<?php

namespace Rogierw\RwAcme\Support;

class JsonWebSignature
{
    public static function generate(array $payload, string $url, string $nonce, string $accountKeysPath): array
    {
        $accountKey = file_get_contents($accountKeysPath . 'private.pem');

        $privateKey = openssl_pkey_get_private($accountKey);

        $protected = [
            'alg' => 'RS256',
            'jwk' => JsonWebKey::compute($accountKey),
            'nonce' => $nonce,
            'url'   => $url,
        ];

        $payload64 = Base64::urlSafeEncode(str_replace('\\/', '/', json_encode($payload)));
        $protected64 = Base64::urlSafeEncode(json_encode($protected));

        openssl_sign($protected64 . '.' . $payload64, $signed, $privateKey, 'SHA256');

        $signed64 = Base64::urlSafeEncode($signed);

        return [
            'protected' => $protected64,
            'payload'   => $payload64,
            'signature' => $signed64,
        ];
    }
}
