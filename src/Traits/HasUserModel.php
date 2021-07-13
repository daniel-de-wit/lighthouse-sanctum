<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Traits;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

trait HasUserModel
{
    /**
     * @param mixed $user
     * @return Model
     * @throws RuntimeException
     */
    protected function getModelFromUser($user): Model
    {
        if (! $user instanceof Model) {
            throw new RuntimeException('The user class must extend "' . Model::class . '".');
        }

        return $user;
    }
}
