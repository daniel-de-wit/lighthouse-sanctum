<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Traits;

use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Config\Repository as Config;
use RuntimeException;

trait CreatesUserProvider
{
    abstract protected function getAuthManager(): AuthManager;

    abstract protected function getConfig(): Config;

    protected function createUserProvider(): UserProvider
    {
        $provider = $this->getConfig()->get('lighthouse-sanctum.provider');

        $userProvider = $this->getAuthManager()->createUserProvider($provider);

        if (! $userProvider) {
            throw new RuntimeException('User provider not found.');
        }

        return $userProvider;
    }
}
