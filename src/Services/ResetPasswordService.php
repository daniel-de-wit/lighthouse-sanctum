<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Services;

use DanielDeWit\LighthouseSanctum\Contracts\Services\ResetPasswordServiceInterface;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Contracts\Auth\CanResetPassword;

class ResetPasswordService implements ResetPasswordServiceInterface
{
    public function setResetPasswordUrl(string $url): void
    {
        ResetPasswordNotification::createUrlUsing(function (CanResetPassword $notifiable, string $token) use ($url) {
            return str_replace([
                '__EMAIL__',
                '__TOKEN__',
            ], [
                $notifiable->getEmailForPasswordReset(),
                $token,
            ], $url);
        });
    }
}
