<?php

namespace Rogierw\RwAcme\Exceptions;

class DomainValidationException extends LetsEncryptClientException
{
    public static function localHttpChallengeTestFailed(string $domain, string $code): self
    {
        return new static(sprintf(
            'The local HTTP challenge test for %s received an invalid response with a %s status code.',
            $domain,
            $code
        ));
    }

    public static function localDnsChallengeTestFailed(string $domain): self
    {
        return new static(sprintf(
            "Couldn't fetch DNS records for %s.",
            $domain
        ));
    }
}
