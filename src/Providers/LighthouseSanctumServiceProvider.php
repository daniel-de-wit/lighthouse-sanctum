<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Providers;

use DanielDeWit\LighthouseSanctum\Contracts\Services\EmailVerificationServiceInterface;
use DanielDeWit\LighthouseSanctum\Contracts\Services\ResetPasswordServiceInterface;
use DanielDeWit\LighthouseSanctum\Services\EmailVerificationService;
use DanielDeWit\LighthouseSanctum\Services\ResetPasswordService;
use Illuminate\Support\ServiceProvider;

class LighthouseSanctumServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(EmailVerificationServiceInterface::class, EmailVerificationService::class);
        $this->app->singleton(ResetPasswordServiceInterface::class, ResetPasswordService::class);
    }

    public function boot(): void
    {
        $this->publishConfig();
        $this->publishSchema();

        $this->mergeConfig();
    }

    protected function publishConfig(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/lighthouse-sanctum.php' => config_path('lighthouse-sanctum.php'),
        ], 'lighthouse-sanctum');
    }

    protected function publishSchema(): void
    {
        $this->publishes([
            __DIR__ . '/../../graphql/sanctum.graphql' => base_path('graphql/sanctum.graphql'),
        ], 'lighthouse-sanctum');
    }

    protected function mergeConfig(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/lighthouse-sanctum.php',
            'lighthouse-sanctum',
        );
    }
}
