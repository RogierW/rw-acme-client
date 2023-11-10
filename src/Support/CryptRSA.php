<?php

namespace Rogierw\RwAcme\Support;

use Rogierw\RwAcme\Exceptions\LetsEncryptClientException;

class CryptRSA
{
    /**
     * @return array{privateKey: string, publicKey: string}
     */
    public static function generate(): array
    {
        $pKey = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'private_key_bits' => 4096,
        ]);

        if (!openssl_pkey_export($pKey, $privateKey)) {
            throw new LetsEncryptClientException('RSA keypair export failed.');
        }

        $details = openssl_pkey_get_details($pKey);

        return [
            'privateKey' => $privateKey,
            'publicKey' => $details['key'],
        ];
    }
}
