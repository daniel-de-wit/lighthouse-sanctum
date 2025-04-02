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

        $this->app->singleton(SignatureServiceInterface::class, function (Container $container): \DanielDeWit\LighthouseSanctum\Services\SignatureService {
            /** @var Config $config */
            $config = $container->make(Config::class);

            /** @var string $appKey */
            $appKey = $config->get('app.key');

            return new SignatureService($appKey);
        });

        $this->app->singleton(EmailVerificationServiceInterface::class, function (Container $container): \DanielDeWit\LighthouseSanctum\Services\EmailVerificationService {
            /** @var Config $config */
            $config = $container->make(Config::class);

            /** @var SignatureServiceInterface $signatureService */
            $signatureService = $container->make(SignatureServiceInterface::class);

            /** @var int $expiresIn */
            $expiresIn = $config->get('auth.verification.expire', 60);

            return new EmailVerificationService($signatureService, $expiresIn);
        });
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
}
