<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Contracts\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

interface ResetPasswordServiceInterface
{
    public function setResetPasswordUrl(string $url): void;

    /**
     * @param Authenticatable|Model $user
     * @param string $password
     */
    public function resetPassword($user, string $password): void;
}
