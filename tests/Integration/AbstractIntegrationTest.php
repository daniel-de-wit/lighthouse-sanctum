<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Tests\Integration;

use DanielDeWit\LighthouseSanctum\Providers\LighthouseSanctumServiceProvider;
use DanielDeWit\LighthouseSanctum\Tests\stubs\Users\UserHasApiTokens;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\NotificationServiceProvider;
use Laravel\Sanctum\SanctumServiceProvider;
use Nuwave\Lighthouse\LighthouseServiceProvider;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Nuwave\Lighthouse\Validation\ValidationServiceProvider;
use Orchestra\Testbench\TestCase;

abstract class AbstractIntegrationTest extends TestCase
{
    use MakesGraphQLRequests;
    use RefreshDatabase;

    /**
     * @param Application $app
     * @return string[]
     */
    protected function getPackageProviders($app): array
    {
        return [
            LighthouseSanctumServiceProvider::class,
            LighthouseServiceProvider::class,
            NotificationServiceProvider::class,
            SanctumServiceProvider::class,
            ValidationServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadLaravelMigrations();
    }

    /**
     * @param Application $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('auth.providers.users.model', UserHasApiTokens::class);
        $app['config']->set('lighthouse.schema.register', $this->getStubsPath('schema.graphql'));
        $app['config']->set('lighthouse.guard', 'sanctum');
    }

    protected function getStubsPath(string $path): string
    {
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . $path;
    }
}
