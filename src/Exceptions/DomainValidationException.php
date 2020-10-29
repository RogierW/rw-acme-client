<?php

namespace Rogierw\RwAcme\Exceptions;

use Exception;

class DomainValidationException extends Exception
{
    public static function invalidChallengeType(string $type): self
    {
        return new static("Invalid challenge type `{$type}` specified.");
    }

    public static function localHttpChallengeTestFailed(string $domain, string $code): self
    {
        return new static(sprintf(
            'The local HTTP challenge test for %s received an invalid response with a %s status code.',
            $domain,
            $code
        ));
    }
}
