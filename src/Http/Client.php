<?php

namespace Rogierw\RwAcme\Http;

class Client
{
    /** @var string */
    private $baseUrl;

    /** @var int */
    private $timeout;

    public function __construct(string $baseUrl, int $timeout = 10)
    {
        $this->baseUrl = $baseUrl;
        $this->timeout = $timeout;
    }

    public function head(string $url)
    {
        return $this->makeCurlRequest('head', $url);
    }

    public function get(string $url, array $headers = [], array $arguments = [])
    {
        return $this->makeCurlRequest('get', $url, $headers, $arguments);
    }

    public function post(string $url, array $payload = [], array $headers = [])
    {
        $headers = array_merge(['Content-Type: application/jose+json'], $headers);

        return $this->makeCurlRequest('post', $url, $headers, $payload);
    }

    public function makeCurlRequest(string $httpVerb, string $fullUrl, array $headers = [], array $payload = []): Response
    {
        $headers = array_merge([
            'Content-Type: ' . ($httpVerb === 'post') ? 'application/jose+json' : 'application/json',
        ], $headers);

        $curlHandle = $this->getCurlHandle($fullUrl, $headers);

        switch ($httpVerb) {
            case 'head':
                curl_setopt($curlHandle, CURLOPT_CUSTOMREQUEST, 'HEAD');
                curl_setopt($curlHandle, CURLOPT_NOBODY, true);
                break;

            case 'get':
                curl_setopt($curlHandle, CURLOPT_URL, $fullUrl . '?' . http_build_query($payload));
                break;

            case 'post':
                curl_setopt($curlHandle, CURLOPT_POST, true);
                $this->attachRequestPayload($curlHandle, $payload);
                break;
        }

        $rawResponse = curl_exec($curlHandle);
        $headerSize = curl_getinfo($curlHandle, CURLINFO_HEADER_SIZE);
        $headers = curl_getinfo($curlHandle);
        $error = curl_error($curlHandle);

        $rawHeaders = mb_substr($rawResponse, 0, $headerSize);
        $rawBody = mb_substr($rawResponse, $headerSize);
        $body = $rawBody;

        if ($headers['content_type'] === 'application/json') {
            $body = json_decode($rawBody, true);
        }

        return new Response($rawHeaders, $headers, $body, $error);
    }

    private function attachRequestPayload(&$curlHandle, array $data)
    {
        $encoded = json_encode($data);

        curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $encoded);
    }

    private function getCurlHandle(string $fullUrl, array $headers = [])
    {
        $curlHandle = curl_init();

        curl_setopt($curlHandle, CURLOPT_URL, $fullUrl);

        curl_setopt($curlHandle, CURLOPT_HTTPHEADER, array_merge([
            'Accept: application/json',
        ], $headers));

        curl_setopt($curlHandle, CURLOPT_USERAGENT, 'rogierw/rw-acme-client');
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlHandle, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curlHandle, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($curlHandle, CURLOPT_ENCODING, '');
        curl_setopt($curlHandle, CURLOPT_HEADER, true);

        return $curlHandle;
    }
}
