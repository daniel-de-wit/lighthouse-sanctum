<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Contracts\Services;

interface SignatureServiceInterface
{
    /**
     * @param array<string, mixed> $params
     * @return string
     */
    public function generate(array $params): string;

    /**
     * @param array<string, mixed> $params
     * @param string               $signature
     */
    public function verify(array $params, string $signature): void;
}
