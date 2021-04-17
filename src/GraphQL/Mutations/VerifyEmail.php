<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\GraphQL\Mutations;

use DanielDeWit\LighthouseSanctum\Enums\EmailVerificationStatus;
use DanielDeWit\LighthouseSanctum\Traits\CreatesUserProvider;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Support\Facades\Validator;
use Nuwave\Lighthouse\Exceptions\ValidationException;
use RuntimeException;

class VerifyEmail
{
    use CreatesUserProvider;

    protected AuthManager $authManager;
    protected Config $config;

    public function __construct(AuthManager $authManager, Config $config)
    {
        $this->authManager = $authManager;
        $this->config      = $config;
    }

    /**
     * @param mixed $_
     * @param array<string, string|int> $args
     * @return array<string, EmailVerificationStatus>
     * @throws ValidationException
     */
    public function __invoke($_, array $args): array
    {
        $userProvider = $this->createUserProvider();

        $user = $userProvider->retrieveById($args['id']);

        if (! $user instanceof MustVerifyEmail) {
            throw new RuntimeException('User not instance of MustVerifyEmail');
        }

        if (! hash_equals((string) $args['hash'],
            sha1($user->getEmailForVerification()))) {
            throw new ValidationException('The provided id and hash are incorrect.', Validator::make([], []));
        }

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
