<?php

namespace Rogierw\RwAcme\Support;

use OpenSSLAsymmetricKey;
use Rogierw\RwAcme\Exceptions\LetsEncryptClientException;

class OpenSsl
{
    public static function generatePrivateKey(): OpenSSLAsymmetricKey
    {
        return openssl_pkey_new([
            'private_key_bits' => 2048,
            'digest_alg' => 'sha256',
        ]);
    }

    public static function openSslKeyToString(OpenSSLAsymmetricKey $key): string
    {
        if (!openssl_pkey_export($key, $output)) {
            throw new LetsEncryptClientException('Exporting SSL key failed.');
        }

        return trim($output);
    }

    public static function generateCsr(
        array $domains,
        OpenSSLAsymmetricKey $privateKey,
        bool $isAssociative = false
    ): string {
        if ($isAssociative) {
            $san = [];

            self::extractKey($domains, $san, 'dns', 'DNS');
            self::extractKey($domains, $san, 'ip', 'IP Address');

            $san = implode(',', $san);

            $dn = implode(',', array_map(function ($key, $value) {
                return $key . ':' . $value;
            }, $domains));
        } else {
            $dn = ['commonName' => $domains[0]];

            $san = implode(',', array_map(function ($dns) {
                return 'DNS:' . $dns;
            }, $domains));
        }

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
            'config' => stream_get_meta_data($tempFile)['uri'],
        ]);

        fclose($tempFile);

        if (!openssl_csr_export($csr, $out)) {
            throw new LetsEncryptClientException('Exporting CSR failed.');
        }

        return trim($out);
    }

    public static function extractKey(array &$domains, array &$san, string $key, string $name): void
    {
        if (!empty($domains[$key])) {
            $san = array_merge($san, array_map(function ($ip) use ($name) {
                return $name . ':' . $ip;
            }, $domains[$key]));
            unset($domains[$key]);
        }
    }
}
