<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\GraphQL\Mutations;

use DanielDeWit\LighthouseSanctum\Contracts\Services\EmailVerificationServiceInterface;
use DanielDeWit\LighthouseSanctum\Enums\RegisterStatus;
use DanielDeWit\LighthouseSanctum\Exceptions\HasApiTokensException;
use DanielDeWit\LighthouseSanctum\Traits\CreatesUserProvider;
use Exception;
use Illuminate\Auth\AuthManager;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Config\Repository as Config;
use Laravel\Sanctum\Contracts\HasApiTokens;

class Register
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
     * @param array<string, mixed> $args
     * @return array<string, RegisterStatus|array|string|null>
     * @throws Exception
     */
    public function __invoke($_, array $args): array
    {
        /** @var EloquentUserProvider $userProvider */
        $userProvider = $this->createUserProvider();

        $user = $userProvider->createModel();
        $user->fill($args);
        $user->save();

        if ($user instanceof MustVerifyEmail) {
            if ($args['verification_url']) {
                $this->emailVerificationService->setVerificationUrl($args['verification_url']['url']);
            }

            $user->sendEmailVerificationNotification();

            return [
                'tokens' => [],
                'status' => RegisterStatus::MUST_VERIFY_EMAIL(),
            ];
        }

        if (! $user instanceof HasApiTokens) {
            throw new HasApiTokensException($user);
        }

        return [
            'token'  => $user->createToken('default')->plainTextToken,
            'status' => RegisterStatus::SUCCESS(),
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
