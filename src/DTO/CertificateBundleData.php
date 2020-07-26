<?php

namespace Rogierw\RwAcme\DTO;

use Rogierw\RwAcme\Http\Response;
use Spatie\DataTransferObject\DataTransferObject;

class CertificateBundleData extends DataTransferObject
{
    /** @var string */
    public $certificate;

    /** @var string */
    public $fullchain;

    public static function fromResponse(Response $response): self
    {
        $certificate = '';
        $fullchain = '';

        if (preg_match_all('~(-----BEGIN\sCERTIFICATE-----[\s\S]+?-----END\sCERTIFICATE-----)~i', $response->getBody(), $matches)) {
            $certificate = $matches[0][0];

            if (count($matches[0]) > 1) {
                $fullchain = $matches[0][0] . "\n";

                for ($i = 1; $i < count($matches[0]); $i++) {
                    $fullchain .= $matches[0][$i] . "\n";
                }
            }
        }

        return new self(compact('certificate', 'fullchain'));
    }
}
