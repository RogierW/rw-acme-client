<?php

namespace Rogierw\RwAcme\Support;

use RuntimeException;

class OpenSsl
{
    public static function generatePrivateKey(): string
    {
        $key = openssl_pkey_new([
            'private_key_bits' => 2048,
            'digest_alg'       => 'sha256',
        ]);

        if (!openssl_pkey_export($key, $out)) {
            throw new RuntimeException('Exporting SSL key failed.');
        }

        return trim($out);
    }

    public static function generateCsr(array $domains, string $privateKey): string
    {
        $dn = ['commonName' => $domains[0]];

        $san = implode(',', array_map(function ($dns) {
            return 'DNS:' . $dns;
        }, $domains));

        $tempFile = tmpfile();

        fwrite(
            $tempFile,
            'HOME = .
			RANDFILE = $ENV::HOME/.rnd
			[ req ]
			default_bits = 4096
			default_keyfile = privkey.pem
			distinguished_name = req_distinguished_name
			req_extensions = v3_req
			[ req_distinguished_name ]
			countryName = Country Name (2 letter code)
			[ v3_req ]
			basicConstraints = CA:FALSE
			subjectAltName = ' . $san . '
			keyUsage = nonRepudiation, digitalSignature, keyEncipherment'
        );

        $csr = openssl_csr_new($dn, $privateKey, [
            'digest_alg' => 'sha256',
            'config'     => stream_get_meta_data($tempFile)['uri'],
        ]);

        fclose($tempFile);

        if (!openssl_csr_export($csr, $out)) {
            throw new RuntimeException('Exporting CSR failed.');
        }

        return trim($out);
    }
}
