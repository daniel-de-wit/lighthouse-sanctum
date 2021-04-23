<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Contracts\Factories;

use Exception;
use Illuminate\Database\QueryException;

interface UniqueValidationExceptionFactoryInterface
{
    public function make(QueryException $exception, string $message, string $path): Exception;
}
