<?php

namespace Rogierw\RwAcme\Support;

class Thumbprint
{
    public static function make(string $accountKey): string
    {
        return JsonWebKey::thumbprint(JsonWebKey::compute($accountKey));
    }
}
