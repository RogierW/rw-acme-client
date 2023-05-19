<?php

namespace Rogierw\RwAcme\DTO;

use Rogierw\RwAcme\Http\Response;
use Spatie\LaravelData\Data;

class CertificateBundleData extends Data
{
    public function __construct(
        public string $certificate,
        public string $fullchain,
    ) {}

    public static function fromResponse(Response $response): CertificateBundleData
    {
        $certificate = '';
        $fullchain = '';

        if (preg_match_all(
            '~(-----BEGIN\sCERTIFICATE-----[\s\S]+?-----END\sCERTIFICATE-----)~i',
            $response->getBody(),
            $matches
        )) {
            $certificate = $matches[0][0];
            $matchesCount = count($matches[0]);

            if ($matchesCount > 1) {
                $fullchain = $matches[0][0] . "\n";

                for ($i = 1; $i < $matchesCount; $i++) {
                    $fullchain .= $matches[0][$i] . "\n";
                }
            }
        }

        return new self(certificate: $certificate, fullchain: $fullchain);
    }
}
