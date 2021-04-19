<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Contracts\Services;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\MustVerifyEmail;

interface EmailVerificationServiceInterface
{
    public function setVerificationUrl(string $url): void;

    /**
     * @param MustVerifyEmail $user
     * @param string          $hash
     * @throws AuthenticationException
     */
    public function verify(MustVerifyEmail $user, string $hash): void;
}
