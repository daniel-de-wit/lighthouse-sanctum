<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Tests\stubs\Users;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserMustVerifyEmail extends UserHasApiTokens implements MustVerifyEmail
{
    protected static function newFactory(): Factory
    {
        return new UserMustVerifyEmailFactory();
    }
}
