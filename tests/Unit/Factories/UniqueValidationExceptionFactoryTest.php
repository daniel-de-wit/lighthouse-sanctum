<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Tests\Unit\Factories;

use DanielDeWit\LighthouseSanctum\Factories\UniqueValidationExceptionFactory;
use DanielDeWit\LighthouseSanctum\Tests\Unit\AbstractUnitTest;
use Illuminate\Database\DatabaseManager;
use Mockery;
use Mockery\MockInterface;

class UniqueValidationExceptionFactoryTest extends AbstractUnitTest
{
    protected UniqueValidationExceptionFactory $factory;

    /**
     * @var DatabaseManager|MockInterface
     */
    protected $databaseManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->databaseManager = Mockery::mock(DatabaseManager::class);

        $this->factory = new UniqueValidationExceptionFactory($this->databaseManager);
    }
}
