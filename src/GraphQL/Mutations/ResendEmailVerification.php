<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\GraphQL\Mutations;

use DanielDeWit\LighthouseSanctum\Contracts\Services\EmailVerificationServiceInterface;
use DanielDeWit\LighthouseSanctum\Traits\CreatesUserProvider;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Config\Repository as Config;

class ResendEmailVerification
{
    use CreatesUserProvider;

    protected AuthManager $authManager;

    protected Config $config;

    protected EmailVerificationServiceInterface $emailVerificationService;

    public function __construct(
        AuthManager $authManager,
        Config $config,
        EmailVerificationServiceInterface $emailVerificationService
    ) {
        $this->authManager              = $authManager;
        $this->config                   = $config;
        $this->emailVerificationService = $emailVerificationService;
    }

    /**
     * @param  mixed  $_
     * @param  array<string, mixed>  $args
     * @return array<string, string>
     */
    public function __invoke($_, array $args): array
    {
        $userProvider = $this->createUserProvider();

        $user = $userProvider->retrieveByCredentials([
            'email' => $args['email'],
        ]);

        if ($user && $user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            if (isset($args['verification_url'])) {
                /** @var array<string, string> $verificationUrl */
                $verificationUrl = $args['verification_url'];

                $this->emailVerificationService->setVerificationUrl($verificationUrl['url']);
            }

            $user->sendEmailVerificationNotification();
        }

        return [
            'status' => 'EMAIL_SENT',
        ];
    }

    protected function getAuthManager(): AuthManager
    {
        return $this->authManager;
    }

    protected function getConfig(): Config
    {
        return $this->config;
    }
}
