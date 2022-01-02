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
use Illuminate\Contracts\Translation\Translator;

class Login
{
    use CreatesUserProvider;

    protected AuthManager $authManager;
    protected Config $config;
    protected Translator $translator;


    public function __construct(
        AuthManager $authManager,
        Config $config,
        Translator $translator
    ) {
        $this->authManager = $authManager;
        $this->config      = $config;
        $this->translator  = $translator;
    }

    /**
     * @param mixed $_
     * @param array<string, string> $args
     * @return string[]
     * @throws Exception
     */
    public function __invoke($_, array $args): array
    {
        $userProvider = $this->createUserProvider();

        $user = $userProvider->retrieveByCredentials([
            'email'    => $args['email'],
            'password' => $args['password'],
        ]);

        if (!$user || !$userProvider->validateCredentials($user, $args)) {
            throw new AuthenticationException(
                $this->translator->get("lighthouse-sanctum::exception.authentication_incorrect_credentials")
            );
        }

        if ($user instanceof MustVerifyEmail && !$user->hasVerifiedEmail()) {
            throw new AuthenticationException(
                $this->translator->get("lighthouse-sanctum::exception.authentication_email_not_verified")
            );
        }

        if (!$user instanceof HasApiTokens) {
            throw new HasApiTokensException($user, $this->translator);
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
