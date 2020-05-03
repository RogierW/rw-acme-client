<?php

namespace Rogierw\RwAcme\Support;

use RuntimeException;

class CryptRSA
{
    public static function generate(string $directory): void
    {
        $res = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'private_key_bits' => 4096,
        ]);

        if (!openssl_pkey_export($res, $privateKey)) {
            throw new RuntimeException('RSA keypair export failed.');
        }

        $details = openssl_pkey_get_details($res);

        file_put_contents($directory . 'private.pem', $privateKey);
        file_put_contents($directory . 'public.pem', $details['key']);

        openssl_pkey_free($res);
    }
}
