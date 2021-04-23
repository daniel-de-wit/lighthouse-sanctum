<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Providers;

use DanielDeWit\LighthouseSanctum\Contracts\Factories\UniqueValidationExceptionFactoryInterface;
use DanielDeWit\LighthouseSanctum\Contracts\Services\EmailVerificationServiceInterface;
use DanielDeWit\LighthouseSanctum\Contracts\Services\ResetPasswordServiceInterface;
use DanielDeWit\LighthouseSanctum\Enums\EmailVerificationStatus;
use DanielDeWit\LighthouseSanctum\Enums\ForgotPasswordStatus;
use DanielDeWit\LighthouseSanctum\Enums\LogoutStatus;
use DanielDeWit\LighthouseSanctum\Enums\RegisterStatus;
use DanielDeWit\LighthouseSanctum\Factories\UniqueValidationExceptionFactory;
use DanielDeWit\LighthouseSanctum\Services\EmailVerificationService;
use DanielDeWit\LighthouseSanctum\Services\ResetPasswordService;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use Nuwave\Lighthouse\Schema\TypeRegistry;
use Nuwave\Lighthouse\Schema\Types\LaravelEnumType;

class LighthouseSanctumServiceProvider extends ServiceProvider
{
    protected TypeRegistry $typeRegistry;

    public function register(): void
    {
        $this->app->singleton(EmailVerificationServiceInterface::class, EmailVerificationService::class);
        $this->app->singleton(ResetPasswordServiceInterface::class, ResetPasswordService::class);
        $this->app->singleton(UniqueValidationExceptionFactoryInterface::class, UniqueValidationExceptionFactory::class);
    }

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

    protected function registerEnums(): void
    {
        $this->typeRegistry->register(
            new LaravelEnumType(RegisterStatus::class),
        );

        $this->typeRegistry->register(
            new LaravelEnumType(LogoutStatus::class),
        );

        $this->typeRegistry->register(
            new LaravelEnumType(EmailVerificationStatus::class),
        );

        $this->typeRegistry->register(
            new LaravelEnumType(ForgotPasswordStatus::class),
        );
    }
}
