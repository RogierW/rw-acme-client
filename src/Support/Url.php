<?php

namespace Rogierw\RwAcme\Support;

class Url
{
    public static function extractId(string $url): string
    {
        if (empty($url)) {
            return '';
        }

        $url = rtrim($url, '/');

        $positionLastSlash = strrpos($url, '/');

        return substr($url, ($positionLastSlash + 1));
    }
}
