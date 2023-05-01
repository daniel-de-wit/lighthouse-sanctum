<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Services;

use DanielDeWit\LighthouseSanctum\Contracts\Services\SignatureServiceInterface;
use Illuminate\Routing\Exceptions\InvalidSignatureException;

class SignatureService implements SignatureServiceInterface
{
    public function __construct(protected string $appKey)
    {
    }

    public function generate(array $params): string
    {
        return hash_hmac('sha256', serialize($params), $this->appKey);
    }

    public function verify(array $params, string $signature): void
    {
        if (! hash_equals($signature, $this->generate($params))) {
            throw new InvalidSignatureException();
        }
    }
}
