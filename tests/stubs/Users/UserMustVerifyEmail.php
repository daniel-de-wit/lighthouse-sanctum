<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Tests\stubs\Users;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserMustVerifyEmail extends UserHasApiTokens implements MustVerifyEmail
{
    /** @use HasFactory<UserMustVerifyEmailFactory> */
    use HasFactory;

    /**
     * @return UserMustVerifyEmailFactory
     */
    protected static function newFactory(): Factory
    {
        return new UserMustVerifyEmailFactory;
    }
}
