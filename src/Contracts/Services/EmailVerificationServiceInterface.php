<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Contracts\Services;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Nuwave\Lighthouse\Exceptions\AuthenticationException;

interface EmailVerificationServiceInterface
{
    public function transformUrl(MustVerifyEmail $user, string $url): string;

    public function setVerificationUrl(string $url): void;

    /**
     * @throws AuthenticationException
     */
    public function verify(MustVerifyEmail $user, string $hash): void;

    /**
     * @throws AuthenticationException
     */
    public function verifySigned(MustVerifyEmail $user, string $hash, int $expires, string $signature): void;

    /**
     * @throws AuthenticationException
     */
    public function throwAuthenticationException(): void;
}
