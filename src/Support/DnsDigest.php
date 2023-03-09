<?php

namespace Rogierw\RwAcme\Support;

class DnsDigest
{
    public static function createHash(string $token, string $thumbprint): string
    {
        return hash(
            'sha256',
            sprintf('%s.%s', $token, $thumbprint),
            true
        );
    }

    public static function make(string $token, string $thumbprint): string
    {
        return Base64::urlSafeEncode(self::createHash($token, $thumbprint));
    }
}
