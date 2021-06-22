<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Tests\Traits;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\Guard;
use Mockery;
use Mockery\MockInterface;

trait MocksAuthFactory
{
    /**
     * @param Authenticatable|MockInterface|null $user
     * @return AuthFactory|MockInterface
     */
    protected function mockAuthFactory($user = null)
    {
        /** @var Guard|MockInterface $guard */
        $guard = Mockery::mock(Guard::class)
            ->shouldReceive('user')
            ->andReturn($user)
            ->getMock();

        /** @var AuthFactory|MockInterface $authFactory */
        $authFactory = Mockery::mock(AuthFactory::class)
            ->shouldReceive('guard')
            ->with('sanctum')
            ->andReturn($guard)
            ->getMock();

        return $authFactory;
    }
}
