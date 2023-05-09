<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Tests\Unit;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

abstract class AbstractUnitTestCase extends TestCase
{
    use MockeryPHPUnitIntegration;
}
