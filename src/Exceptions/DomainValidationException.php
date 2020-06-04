<?php

namespace Rogierw\RwAcme\Exceptions;

use Exception;

class DomainValidationException extends Exception
{
    public static function invalidChallengeType(string $type)
    {
        return new static("Invalid challenge type `{$type}` specified.");
    }
}
