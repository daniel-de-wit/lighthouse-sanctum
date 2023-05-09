<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\GraphQL\Mutations;

use DanielDeWit\LighthouseSanctum\Contracts\Services\EmailVerificationServiceInterface;
use DanielDeWit\LighthouseSanctum\Exceptions\HasApiTokensException;
use DanielDeWit\LighthouseSanctum\Traits\CreatesUserProvider;
use Exception;
use Illuminate\Auth\AuthManager;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Laravel\Sanctum\Contracts\HasApiTokens;

class Register
{
    use CreatesUserProvider;

    public function __construct(
        protected AuthManager $authManager,
        protected Config $config,
        protected Hasher $hash,
        protected EmailVerificationServiceInterface $emailVerificationService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, string|null>
     *
     * @throws Exception
     */
    public function __invoke(mixed $_, array $args): array
    {
        /** @var EloquentUserProvider $userProvider */
        $userProvider = $this->createUserProvider();

        $user = $this->saveUser(
            $userProvider->createModel(),
            $this->getPropertiesFromArgs($args),
        );

        if ($user instanceof MustVerifyEmail) {
            if (isset($args['verification_url'])) {
                /** @var array<string, string> $verificationUrl */
                $verificationUrl = $args['verification_url'];

                $this->emailVerificationService->setVerificationUrl($verificationUrl['url']);
            }

            $user->sendEmailVerificationNotification();

            return [
                'token'  => null,
                'status' => 'MUST_VERIFY_EMAIL',
            ];
        }

        if (! $user instanceof HasApiTokens) {
            throw new HasApiTokensException($user);
        }

        return [
            'token'  => $user->createToken('default')->plainTextToken,
            'status' => 'SUCCESS',
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function saveUser(Model $user, array $attributes): Model
    {
        $user
            ->fill($attributes)
            ->save();

        return $user;
    }

    /**
     * @param  array<string, mixed>  $args
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
