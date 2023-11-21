<?php

namespace Rogierw\RwAcme\Support;

class JsonWebSignature
{
    public static function generate(
        array $payload,
        string $url,
        string $nonce,
        #[\SensitiveParameter] string $accountPrivateKey
    ): array {
        $privateKey = openssl_pkey_get_private($accountPrivateKey);

        $protected = [
            'alg' => 'RS256',
            'jwk' => JsonWebKey::compute($accountPrivateKey),
            'nonce' => $nonce,
            'url' => $url,
        ];

        $payload64 = Base64::urlSafeEncode(str_replace('\\/', '/', json_encode($payload, JSON_THROW_ON_ERROR)));
        $protected64 = Base64::urlSafeEncode(json_encode($protected, JSON_THROW_ON_ERROR));

        openssl_sign($protected64.'.'.$payload64, $signed, $privateKey, 'SHA256');

        $signed64 = Base64::urlSafeEncode($signed);

        return [
            'protected' => $protected64,
            'payload' => $payload64,
            'signature' => $signed64,
        ];
    }
}
