<?php

namespace Rogierw\RwAcme\Http;

use Rogierw\RwAcme\Support\Str;

class Response
{
    private $rawHeaders;
    private $headers;
    private $body;
    private $error;

    public function __construct($rawHeaders, $headers, $body, $error)
    {
        $this->rawHeaders = $rawHeaders;
        $this->headers = $headers;
        $this->body = $body;
        $this->error = $error;
    }

    /** @return mixed */
    public function getRawHeaders()
    {
        $headers = explode("\n", $this->rawHeaders);
        $headersArr = [];

        foreach ($headers as $header) {
            if (!Str::contains($header, ':')) {
                continue;
            }

            [$name, $value] = explode(':', $header, 2);

            $headersArr[$name] = $value;
        }

        return $headersArr;
    }

    /** @return mixed */
    public function getHeaders()
    {
        return $this->headers;
    }

    /** @return mixed */
    public function getBody()
    {
        return $this->body;
    }

    /** @return bool */
    public function hasBody()
    {
        return $this->body != false;
    }

    /** @return mixed */
    public function getError()
    {
        return $this->error;
    }

    /** @return null|int */
    public function getHttpResponseCode()
    {
        if (!isset($this->headers['http_code'])) {
            return;
        }

        return (int) $this->headers['http_code'];
    }
}
