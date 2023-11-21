<?php

namespace Rogierw\RwAcme\Interfaces;

use Rogierw\RwAcme\Http\Response;

interface HttpClientInterface
{
    public function __construct(int $timeout = 10);

    public function head(string $url): Response;

    public function get(string $url, array $headers = [], array $arguments = [], int $maxRedirects = 0): Response;

    public function post(string $url, array $payload = [], array $headers = [], int $maxRedirects = 0): Response;
}
