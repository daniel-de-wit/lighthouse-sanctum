<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Services;

use DanielDeWit\LighthouseSanctum\Contracts\Services\EmailVerificationServiceInterface;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Nuwave\Lighthouse\Exceptions\AuthenticationException;

class EmailVerificationService implements EmailVerificationServiceInterface
{
    public function setVerificationUrl(string $url): void
    {
        VerifyEmail::createUrlUsing(function ($notifiable) use ($url) {
            return str_replace([
                '__ID__',
                '__HASH__',
            ], [
                $notifiable->getKey(),
                $this->createHash($notifiable),
            ], $url);
        });
    }

    /**
     * @param MustVerifyEmail $user
     * @param string          $hash
     * @throws AuthenticationException
     */
    public function verify(MustVerifyEmail $user, string $hash): void
    {
        if (! hash_equals($hash, $this->createHash($user))) {
            throw new AuthenticationException('The provided id and hash are incorrect.');
        }
    }

    protected function createHash(MustVerifyEmail $user): string
    {
        return sha1($user->getEmailForVerification());
    }
}
