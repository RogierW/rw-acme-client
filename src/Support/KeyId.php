<?php

namespace Rogierw\RwAcme\Support;

class KeyId
{
    public static function generate(string $accountKeysPath, string $kid, string $url, string $nonce, ?array $payload = null): array
    {
        $privateKey = openssl_pkey_get_private(file_get_contents($accountKeysPath . 'private.pem'));

        $data = [
            'alg'   => 'RS256',
            'kid'   => $kid,
            'nonce' => $nonce,
            'url'   => $url,
        ];

        $payload = is_array($payload)
            ? str_replace('\\/', '/', json_encode($payload))
            : '';

        $payload64 = Base64::urlSafeEncode($payload);
        $protected64 = Base64::urlSafeEncode(json_encode($data));

        openssl_sign(
            $protected64 . '.' . $payload64,
            $signed,
            $privateKey,
            'SHA256'
        );

        $signed64 = Base64::urlSafeEncode($signed);

        return [
            'protected' => $protected64,
            'payload'   => $payload64,
            'signature' => $signed64,
        ];
    }
}
