<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Providers;

use DanielDeWit\LighthouseSanctum\Enums\RegisterStatus;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use Nuwave\Lighthouse\Events\BuildSchemaString;
use Nuwave\Lighthouse\Schema\TypeRegistry;
use Nuwave\Lighthouse\Schema\Types\LaravelEnumType;

class LighthouseSanctumServiceProvider extends ServiceProvider
{
    protected TypeRegistry $typeRegistry;

    public function boot(Dispatcher $dispatcher, TypeRegistry $typeRegistry): void
    {
        $this->typeRegistry = $typeRegistry;

        $this->publishConfig();
        $this->publishSchema();

        $this->mergeConfig();

        $this->registerEnums();
    }

    protected function publishConfig(): void
    {
        $this->publishes([
            __DIR__.'/../../config/lighthouse-sanctum.php' => config_path('lighthouse-sanctum.php'),
        ], 'lighthouse-sanctum');
    }

    protected function publishSchema(): void
    {
        $this->publishes([
            __DIR__.'/../../graphql/sanctum.graphql' => base_path('graphql/sanctum.graphql'),
        ], 'lighthouse-sanctum');
    }

    protected function mergeConfig(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/lighthouse-sanctum.php',
            'lighthouse-sanctum',
        );
    }

    protected function registerEnums(): void
    {
        $this->typeRegistry->register(
            new LaravelEnumType(RegisterStatus::class),
        );
    }
}
