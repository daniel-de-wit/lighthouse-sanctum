<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Factories;

use DanielDeWit\LighthouseSanctum\Contracts\Factories\UniqueValidationExceptionFactoryInterface;
use DanielDeWit\LighthouseSanctum\Exceptions\UniqueValidationException;
use Exception;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\QueryException;

class UniqueValidationExceptionFactory implements UniqueValidationExceptionFactoryInterface
{
    protected DatabaseManager $databaseManager;

    public function __construct(DatabaseManager $databaseManager)
    {
        $this->databaseManager = $databaseManager;
    }

    public function make(QueryException $exception, string $message, string $path): Exception
    {
        switch ($this->databaseManager->getDriverName()) {
            case 'mysql':
            case 'sqlite':
                return $this->getMySqlException($exception, $message, $path);

            case 'pgsql':
                return $this->getPgSqlException($exception, $message, $path);

            case 'sqlsrv':
                return $this->getSqlSrvException($exception, $message, $path);
        }

        return $exception;
    }

    protected function getMySqlException(QueryException $exception, string $message, string $path): Exception
    {
        if (stripos($exception->getMessage(), 'unique constraint failed') === false) {
            return $exception;
        }

        return new UniqueValidationException($message, $path);
    }

    protected function getPgSqlException(QueryException $exception, string $message, string $path): Exception
    {
        if (stripos($exception->getMessage(), 'duplicate key value violates unique constraint') === false) {
            return $exception;
        }

        return new UniqueValidationException($message, $path);
    }

    protected function getSqlSrvException(QueryException $exception, string $message, string $path): Exception
    {
        if (stripos($exception->getMessage(), 'violation of unique key constraint') === false) {
            return $exception;
        }

        return new UniqueValidationException($message, $path);
    }
}
