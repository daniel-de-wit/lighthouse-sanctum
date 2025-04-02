<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Tests\stubs\Users;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\Contracts\HasApiTokens as HasApiTokensContract;
use Laravel\Sanctum\HasApiTokens;

class UserHasApiTokensIdentifiedByUsername extends User implements HasApiTokensContract
{
    use HasApiTokens;
    /** @use HasFactory<UserHasApiTokensIdentifiedByUsernameFactory> */
    use HasFactory;
    use Notifiable;

    /**
     * @var string
     */
    protected $table = 'users_with_usernames';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'password',
        'email_verified_at',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * @return UserHasApiTokensIdentifiedByUsernameFactory
     */
    protected static function newFactory(): Factory
    {
        return new UserHasApiTokensIdentifiedByUsernameFactory();
    }
}
