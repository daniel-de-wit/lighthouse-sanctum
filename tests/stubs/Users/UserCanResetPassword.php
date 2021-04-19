<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Tests\stubs\Users;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Notifications\Notifiable;

class UserCanResetPassword extends UserHasApiTokens
{
    use Notifiable;

    protected static function newFactory(): Factory
    {
        return new UserCanResetPasswordFactory();
    }
}
