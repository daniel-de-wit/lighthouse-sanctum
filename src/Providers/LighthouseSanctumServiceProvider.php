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
    public function boot(Dispatcher $dispatcher, TypeRegistry $typeRegistry): void
    {
        $dispatcher->listen(BuildSchemaString::class, function (): string {
            if (config('lighthouse-sanctum.schema')) {
                return file_get_contents(config('lighthouse-sanctum.schema'));
            }

            return file_get_contents(__DIR__ . '/../../graphql/sanctum.graphql');
        });

        $this->publishes([
            __DIR__.'/../graphql/' => __DIR__ . '/../../graphql/sanctum.graphql',
        ], 'lighthouse-sanctum');

        $typeRegistry->register(
            new LaravelEnumType(RegisterStatus::class),
        );

        $this->mergeConfigFrom(
            __DIR__.'/../../config/lighthouse-sanctum.php',
            'lighthouse-sanctum',
        );

        $this->publishes([
            __DIR__.'/../../config/lighthouse-sanctum.php' => config_path('lighthouse-sanctum.php'),
        ], 'config');
    }
}
