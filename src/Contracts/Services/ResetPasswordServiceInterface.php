<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Contracts\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Eloquent\Model;

interface ResetPasswordServiceInterface
{
    public function transformUrl(CanResetPassword $notifiable, string $token, string $url): string;

    public function setResetPasswordUrl(string $url): void;

    /**
     * @param Authenticatable|Model $user
     * @param string $password
     */
    public function resetPassword($user, string $password): void;
}
