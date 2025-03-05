<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Tests\stubs\Users;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Orchestra\Testbench\Factories\UserFactory;

/**
 * @extends Factory<UserHasApiTokens>
 */
class UserHasApiTokensFactory extends UserFactory
{
    /**
     * @var class-string<Model>
     */
    protected $model = UserHasApiTokens::class;
}
