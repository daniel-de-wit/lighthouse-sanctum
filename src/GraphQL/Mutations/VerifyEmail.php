<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\GraphQL\Mutations;

use DanielDeWit\LighthouseSanctum\Contracts\Services\EmailVerificationServiceInterface;
use DanielDeWit\LighthouseSanctum\Enums\EmailVerificationStatus;
use DanielDeWit\LighthouseSanctum\Traits\CreatesUserProvider;
use Exception;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Config\Repository as Config;
use Nuwave\Lighthouse\Exceptions\AuthenticationException;
use RuntimeException;

class VerifyEmail
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
     * @param mixed $_
     * @param array<string, string|int> $args
     * @return array<string, EmailVerificationStatus>
     * @throws Exception
     */
    public function __invoke($_, array $args): array
    {
        $userProvider = $this->createUserProvider();

        $user = $userProvider->retrieveById($args['id']);

        if (! $user) {
            throw new AuthenticationException('The provided id and hash are incorrect.');
        }

        if (! $user instanceof MustVerifyEmail) {
            throw new RuntimeException('User must implement "' . MustVerifyEmail::class . '".');
        }

        $this->emailVerificationService->verify($user, (string) $args['hash']);

        $user->markEmailAsVerified();

        return [
            'status' => EmailVerificationStatus::VERIFIED(),
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
