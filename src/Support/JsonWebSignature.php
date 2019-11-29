<?php

namespace Rogierw\Letsencrypt\Support;

class JsonWebSignature
{
    public static function generate(array $payload, string $url, string $nonce, string $accountKeysPath): array
    {
        $privateKey = openssl_pkey_get_private(file_get_contents($accountKeysPath . 'private.pem'));
        $details = openssl_pkey_get_details($privateKey);

        $protected = [
            'alg' => 'RS256',
            'jwk' => [
                'kty' => 'RSA',
                'n' => Base64::UrlSafeEncode($details["rsa"]["n"]),
                'e' => Base64::UrlSafeEncode($details["rsa"]["e"]),
            ],
            'nonce' => $nonce,
            'url' => $url,
        ];

        $payload64 = Base64::urlSafeEncode(str_replace('\\/', '/', json_encode($payload)));
        $protected64 = Base64::urlSafeEncode(json_encode($protected));

        openssl_sign($protected64 . '.' . $payload64, $signed, $privateKey, 'SHA256');

        $signed64 = Base64::urlSafeEncode($signed);

        return [
            'protected' => $protected64,
            'payload' => $payload64,
            'signature' => $signed64,
        ];
    }
}
