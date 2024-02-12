<?php

namespace Rogierw\RwAcme\Support;

use Exception;
use Rogierw\RwAcme\Exceptions\DomainValidationException;
use Rogierw\RwAcme\Interfaces\HttpClientInterface;
use Spatie\Dns\Dns;

class LocalChallengeTest
{
    private static const DEFAULT_NAMESERVER = 'dns.google.com';

    public static function http(string $domain, string $token, string $keyAuthorization, HttpClientInterface $httpClient): void
    {
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
            $dnsResolver = new Dns();

            // Get the nameserver.
            $soaRecord = $dnsResolver->getRecords($domain, DNS_SOA);

            $nameserver = empty($soaRecord)
                ? self::DEFAULT_NAMESERVER
                : $soaRecord[0]->mname();

            $records = $dnsResolver
                ->useNameserver($nameserver)
                ->getRecords(sprintf('%s.%s', $name, $domain), DNS_TXT);

            foreach ($records as $record) {
                if ($record->txt() === $value) {
                    return;
                }
            }
        } catch (Exception $exception) {}

        throw DomainValidationException::localDnsChallengeTestFailed($domain);
    }
}
