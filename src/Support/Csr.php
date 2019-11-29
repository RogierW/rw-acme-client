<?php

namespace Rogierw\Letsencrypt\Support;

use RuntimeException;

class Csr
{
    public static function generateCSR($name, $key)
    {
        $csr = openssl_csr_new($name, $key, [
            'digest_alg' => 'sha256',
        ]);

        if (!openssl_csr_export($csr, $out)) {
            throw new RuntimeException('Exporting CSR failed');
        }

        return $out;
    }
}
