<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Tests\Traits;

use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Foundation\Auth\User;
use Mockery;
use Mockery\MockInterface;

trait MocksUserProvider
{
    protected function mockAuthManager(?UserProvider $userProvider): \Illuminate\Auth\AuthManager|\Mockery\MockInterface
    {
        /** @var AuthManager|MockInterface $authManager */
        $authManager = Mockery::mock(AuthManager::class)
            ->shouldReceive('createUserProvider')
            ->with('sanctum-provider')
            ->andReturn($userProvider)
            ->getMock();

        return $authManager;
    }

    /**
     * @return Config|MockInterface
     */
    protected function mockConfig(): Config|\Mockery\MockInterface
    {
        /** @var Config|MockInterface $config */
        $config = Mockery::mock(Config::class)
            ->shouldReceive('get')
            ->with('lighthouse-sanctum.provider')
            ->andReturn('sanctum-provider')
            ->getMock()
            ->shouldReceive('get')
            ->with('lighthouse-sanctum.user_identifier_field_name', 'email')
            ->andReturn('email')
            ->getMock();

        return $config;
    }

    abstract protected function mockUserProvider(?User $user): \Illuminate\Contracts\Auth\UserProvider|\Mockery\MockInterface;
}
