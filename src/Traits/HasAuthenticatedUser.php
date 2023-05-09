<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Traits;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use RuntimeException;

trait HasAuthenticatedUser
{
    abstract protected function getAuthFactory(): AuthFactory;

    /**
     * @throws RuntimeException
     */
    protected function getAuthenticatedUser(): Authenticatable
    {
        $user = $this->getAuthFactory()
            ->guard('sanctum')
            ->user();

        if (! $user) {
            throw new RuntimeException('Unable to detect current user.');
        }

        return $user;
    }
}
