<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Tests\stubs\Users;

use Orchestra\Testbench\Factories\UserFactory;

class UserHasApiTokensFactory extends UserFactory
{
    protected $model = UserHasApiTokens::class;
}
