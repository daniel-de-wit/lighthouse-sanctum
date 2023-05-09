<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\GraphQL\Mutations;

use DanielDeWit\LighthouseSanctum\Exceptions\HasApiTokensException;
use DanielDeWit\LighthouseSanctum\Traits\CreatesUserProvider;
use Exception;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Config\Repository as Config;
use Laravel\Sanctum\Contracts\HasApiTokens;
use Nuwave\Lighthouse\Exceptions\AuthenticationException;

class Login
{
    use CreatesUserProvider;

    public function __construct(
        protected AuthManager $authManager,
        protected Config $config,
    ) {
    }

    /**
     * @param  array<string, string>  $args
     * @return string[]
     *
     * @throws Exception
     */
    public function __invoke(mixed $_, array $args): array
    {
        $userProvider = $this->createUserProvider();

        $identificationKey = $this->getConfig()
            ->get('lighthouse-sanctum.user_identifier_field_name', 'email');

        $user = $userProvider->retrieveByCredentials([
            $identificationKey => $args[$identificationKey],
            'password'         => $args['password'],
        ]);

        if (! $user || ! $userProvider->validateCredentials($user, $args)) {
            throw new AuthenticationException('The provided credentials are incorrect.');
        }

        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            throw new AuthenticationException('Your email address is not verified.');
        }

        if (! $user instanceof HasApiTokens) {
            throw new HasApiTokensException($user);
        }

        return [
            'token' => $user->createToken('default')->plainTextToken,
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
