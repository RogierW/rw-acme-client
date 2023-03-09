<?php

namespace Rogierw\RwAcme\Support;

use Rogierw\RwAcme\Exceptions\DomainValidationException;
use Rogierw\RwAcme\Http\Client;

class LocalChallengeTest
{
    public static function http(string $domain, string $token, string $keyAuthorization): void
    {
        $httpClient = new Client(10, 1);

        $response = $httpClient->get($domain . '/.well-known/acme-challenge/' . $token);

        $body = $response->getBody();

        if (is_array($body)) {
            $body = json_encode($body);
        }

        if (trim($body) === $keyAuthorization) {
            return;
        }

        throw DomainValidationException::localHttpChallengeTestFailed(
            $domain,
            $response->getHttpResponseCode() ?? 'unknown'
        );
    }

    public static function dns(string $domain, string $name, string $value): void
    {
        $response = @dns_get_record(sprintf('%s.%s', $name, $domain), DNS_TXT);

        if (! empty($response[0]['txt']) && $response[0]['txt'] === $value) {
            return;
        }

        throw DomainValidationException::localDnsChallengeTestFailed($domain);
    }
}
