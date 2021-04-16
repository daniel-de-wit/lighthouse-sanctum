<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Tests\stubs\Users;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User;
use Laravel\Sanctum\HasApiTokens;

class UserHasApiTokens extends User
{
    use HasApiTokens;
    use HasFactory;

    protected $table = 'users';

    protected static function newFactory(): Factory
    {
        return new UserHasApiTokensFactory();
    }
}
