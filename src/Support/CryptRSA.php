<?php

namespace Rogierw\RwAcme\Support;

use Rogierw\RwAcme\Exceptions\LetsEncryptClientException;

class CryptRSA
{
    public static function generate(string $directory): void
    {
        $pKey = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'private_key_bits' => 4096,
        ]);

        if (!openssl_pkey_export($pKey, $privateKey)) {
            throw new LetsEncryptClientException('RSA keypair export failed.');
        }

        $details = openssl_pkey_get_details($pKey);

        file_put_contents($directory . 'private.pem', $privateKey);
        file_put_contents($directory . 'public.pem', $details['key']);
    }
}
