<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Providers;

use DanielDeWit\LighthouseSanctum\Contracts\Services\EmailVerificationServiceInterface;
use DanielDeWit\LighthouseSanctum\Contracts\Services\ResetPasswordServiceInterface;
use DanielDeWit\LighthouseSanctum\Contracts\Services\SignatureServiceInterface;
use DanielDeWit\LighthouseSanctum\Services\EmailVerificationService;
use DanielDeWit\LighthouseSanctum\Services\ResetPasswordService;
use DanielDeWit\LighthouseSanctum\Services\SignatureService;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;

class LighthouseSanctumServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ResetPasswordServiceInterface::class, ResetPasswordService::class);

        $this->app->singleton(SignatureServiceInterface::class, function (Container $container) {
            /** @var Config $config */
            $config = $container->make(Config::class);

            return new SignatureService($config->get('app.key'));
        });

        $this->app->singleton(EmailVerificationServiceInterface::class, function (Container $container) {
            /** @var Config $config */
            $config = $container->make(Config::class);

            return new EmailVerificationService(
                $container->make(SignatureServiceInterface::class),
                $config->get('auth.verification.expire', 60),
                $container->make('translator'),
            );
        });
    }

    public function boot(): void
    {
        $this->publishConfig();
        $this->publishSchema();
        $this->publishTranslations();

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
    protected function publishTranslations(): void
    {

        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'lighthouse-sanctum');

        $this->publishes([
            __DIR__.'/../../resources/lang' => resource_path('lang/vendor/lighthouse-sanctum'),
        ], 'lighthouse-sanctum-translations');
    }

    protected function mergeConfig(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/lighthouse-sanctum.php',
            'lighthouse-sanctum',
        );
    }
}
