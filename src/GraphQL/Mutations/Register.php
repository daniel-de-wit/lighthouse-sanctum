<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\GraphQL\Mutations;

use DanielDeWit\LighthouseSanctum\Contracts\Factories\UniqueValidationExceptionFactoryInterface;
use DanielDeWit\LighthouseSanctum\Contracts\Services\EmailVerificationServiceInterface;
use DanielDeWit\LighthouseSanctum\Enums\RegisterStatus;
use DanielDeWit\LighthouseSanctum\Exceptions\HasApiTokensException;
use DanielDeWit\LighthouseSanctum\Traits\CreatesUserProvider;
use Exception;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Auth\AuthManager;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Laravel\Sanctum\Contracts\HasApiTokens;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class Register
{
    use CreatesUserProvider;

    protected AuthManager $authManager;
    protected Config $config;
    protected Hasher $hash;
    protected EmailVerificationServiceInterface $emailVerificationService;
    protected UniqueValidationExceptionFactoryInterface $uniqueValidationExceptionFactory;

    public function __construct(
        AuthManager $authManager,
        Config $config,
        Hasher $hash,
        EmailVerificationServiceInterface $emailVerificationService,
        UniqueValidationExceptionFactoryInterface $uniqueValidationExceptionFactory
    ) {
        $this->authManager                      = $authManager;
        $this->config                           = $config;
        $this->hash                             = $hash;
        $this->emailVerificationService         = $emailVerificationService;
        $this->uniqueValidationExceptionFactory = $uniqueValidationExceptionFactory;
    }

    /**
     * @param mixed $_
     * @param array<string, mixed> $args
     * @param GraphQLContext $context
     * @param ResolveInfo $resolveInfo
     * @return array<string, RegisterStatus|array|string|null>
     * @throws Exception
     */
    public function __invoke($_, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array
    {
        /** @var EloquentUserProvider $userProvider */
        $userProvider = $this->createUserProvider();

        $user = $userProvider->createModel()->fill($this->getPropertiesFromArgs($args));

        try {
            $user->save();
        } catch (QueryException $exception) {
            throw $this->uniqueValidationExceptionFactory->make(
                $exception,
                'The input must be unique.',
                implode('.', $resolveInfo->path),
            );
        }

        if ($user instanceof MustVerifyEmail) {
            if (isset($args['verification_url'])) {
                $this->emailVerificationService->setVerificationUrl($args['verification_url']['url']);
            }

            $user->sendEmailVerificationNotification();

            return [
                'token'  => null,
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

    /**
     * @param array<string, mixed> $args
     * @return array<string, string>
     */
    protected function getPropertiesFromArgs(array $args): array
    {
        $properties = Arr::except($args, [
            'directive',
            'password_confirmation',
            'verification_url',
        ]);

        $properties['password'] = $this->hash->make($properties['password']);

        return $properties;
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
