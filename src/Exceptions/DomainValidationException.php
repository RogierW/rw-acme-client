<?php

namespace Rogierw\RwAcme\Exceptions;

use Exception;

class DomainValidationException extends Exception
{
    public static function invalidChallengeType(string $type): self
    {
        return new static("Invalid challenge type `{$type}` specified.");
    }

    public static function localHttpChallengeTestFailed(string $code): self
    {
        return new static("The local HTTP challenge test received an invalid response with a {$code} status code.");
    }
}