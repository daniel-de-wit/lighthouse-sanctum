<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Contracts\Services;

interface ResetPasswordServiceInterface
{
    public function setResetPasswordUrl(string $url): void;
}
