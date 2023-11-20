<?php

namespace Rogierw\RwAcme\Support;

use Rogierw\RwAcme\Exceptions\DomainValidationException;
use Rogierw\RwAcme\Http\Client;
use Rogierw\RwAcme\Interfaces\HttpClientInterface;

class LocalChallengeTest
{
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
        $response = @dns_get_record(sprintf('%s.%s', $name, $domain), DNS_TXT);

        if (!in_array($value, array_column($response, 'txt'), true)) {
            throw DomainValidationException::localDnsChallengeTestFailed($domain);
        }
    }
}
