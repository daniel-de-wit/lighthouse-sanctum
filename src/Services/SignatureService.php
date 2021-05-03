<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Services;

use DanielDeWit\LighthouseSanctum\Contracts\Services\SignatureServiceInterface;
use Illuminate\Routing\Exceptions\InvalidSignatureException;

class SignatureService implements SignatureServiceInterface
{
    protected string $appKey;

    public function __construct(string $appKey)
    {
        $this->appKey = $appKey;
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
