<?php

namespace Rogierw\RwAcme\Support;

class KeyId
{
    public static function generate(
        #[\SensitiveParameter] string $accountPrivateKey,
        string $kid,
        string $url,
        string $nonce,
        ?array $payload = null
    ): array {
        $privateKey = openssl_pkey_get_private($accountPrivateKey);

        $data = [
            'alg' => 'RS256',
            'kid' => $kid,
            'nonce' => $nonce,
            'url' => $url,
        ];

        $payload = is_array($payload)
            ? str_replace('\\/', '/', json_encode($payload))
            : '';

        $payload64 = Base64::urlSafeEncode($payload);
        $protected64 = Base64::urlSafeEncode(json_encode($data));

        openssl_sign(
            $protected64.'.'.$payload64,
            $signed,
            $privateKey,
            'SHA256'
        );

        $signed64 = Base64::urlSafeEncode($signed);

        return [
            'protected' => $protected64,
            'payload' => $payload64,
            'signature' => $signed64,
        ];
    }
}
