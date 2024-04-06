<?php

namespace Rogierw\RwAcme\Support;

use Rogierw\RwAcme\Exceptions\DomainValidationException;
use Rogierw\RwAcme\Interfaces\HttpClientInterface;
use RuntimeException;
use Spatie\Dns\Dns;

class LocalChallengeTest
{
    private const DEFAULT_NAMESERVER = 'dns.google.com';

    public static function http(
        string $domain,
        string $token,
        string $keyAuthorization,
        HttpClientInterface $httpClient
    ): void {
        $response = $httpClient->get($domain . '/.well-known/acme-challenge/' . $token, maxRedirects: 1);

        $body = $response->getBody();

        if (is_array($body)) {
            $body = json_encode($body, JSON_THROW_ON_ERROR);
        }

        if (trim($body) === $keyAuthorization) {
            return;
        }

        throw DomainValidationException::localHttpChallengeTestFailed(
            $domain,
            $response->getHttpResponseCode()
        );
    }

    public static function dns(string $domain, string $name, string $value): void
    {
        try {
            $challenge = sprintf('%s.%s', $name, $domain);

            // Try to validate TXT records directly.
            $nameserver = self::getNameserver($domain);
            $txtRecords = self::getRecords($nameserver, $challenge, DNS_TXT);
            if (self::validateTxtRecords($txtRecords, $value)) {
                return;
            }

            // Try to validate a CNAME record pointing to a TXT record containing the correct value.
            $cnameRecords = self::getRecords($nameserver, $challenge, DNS_CNAME);
            if (self::validateCnameRecords($cnameRecords, $value)) {
                return;
            }
        } catch (RuntimeException) {
            // An exception can be thrown by the Dns class when a lookup fails.
        }

        throw DomainValidationException::localDnsChallengeTestFailed($domain);
    }

    private static function validateTxtRecords(array $records, string $value): bool
    {
        foreach ($records as $record) {
            if ($record->txt() === $value) {
                return true;
            }
        }

        return false;
    }

    private static function validateCnameRecords(array $records, string $value): bool
    {
        foreach ($records as $record) {
            $nameserver = self::getNameserver($record->target());
            $txtRecords = self::getRecords($nameserver, $record->target(), DNS_TXT);
            if (self::validateTxtRecords($txtRecords, $value)) {
                return true;
            }

            // If this is another CNAME, follow it.
            $cnameRecords = self::getRecords($nameserver, $record->target(), DNS_CNAME);
            if (!empty($cnameRecords)) {
                if (self::validateCnameRecords($cnameRecords, $value)) {
                    return true;
                }
            }
        }

        return false;
    }

    private static function getNameserver(string $domain): string
    {
        $dnsResolver = new Dns();

        $result = $dnsResolver->getRecords($domain, DNS_NS);

        return empty($result)
            ? self::DEFAULT_NAMESERVER
            : $result[0]->target();
    }

    private static function getRecords(string $nameserver, string $name, int $dnsType): array
    {
        $dnsResolver = new Dns();

        return $dnsResolver
            ->useNameserver($nameserver)
            ->getRecords($name, $dnsType);
    }
}
