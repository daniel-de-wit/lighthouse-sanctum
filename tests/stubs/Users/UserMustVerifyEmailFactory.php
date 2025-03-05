<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Tests\stubs\Users;

use Illuminate\Database\Eloquent\Model;
use Orchestra\Testbench\Factories\UserFactory;

/**
 * @extends UserFactory<UserMustVerifyEmail>
 */
class UserMustVerifyEmailFactory extends UserFactory
{
    /**
     * @var class-string<Model>
     */
    protected $model = UserMustVerifyEmail::class;
}
