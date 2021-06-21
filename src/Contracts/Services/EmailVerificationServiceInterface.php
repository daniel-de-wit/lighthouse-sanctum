<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Contracts\Services;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Nuwave\Lighthouse\Exceptions\AuthenticationException;

interface EmailVerificationServiceInterface
{
    public function setVerificationUrl(string $url): void;

    /**
     * @param MustVerifyEmail $user
     * @param string          $hash
     * @throws AuthenticationException
     */
    public function verify(MustVerifyEmail $user, string $hash): void;

    /**
     * @param MustVerifyEmail $user
     * @param string          $hash
     * @param int             $expires
     * @param string          $signature
     * @throws AuthenticationException
     */
    public function verifySigned(MustVerifyEmail $user, string $hash, int $expires, string $signature): void;

    /**
     * @throws AuthenticationException
     */
    public function throwAuthenticationException(): void;
}
