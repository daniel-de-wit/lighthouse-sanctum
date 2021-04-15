<?php

namespace DanielDeWit\LighthouseSanctum\GraphQL\Mutations;

use Exception;
use Illuminate\Auth\AuthManager;
use Nuwave\Lighthouse\Exceptions\AuthenticationException;
use RuntimeException;

class Login
{
    protected AuthManager $authManager;

    public function __construct(AuthManager $authManager)
    {
        $this->authManager = $authManager;
    }

    /**
     * @param null $_
     * @param string[] $args
     * @return string[]
     * @throws Exception
     */
    public function __invoke($_, array $args): array
    {
        $userProvider = $this->authManager->createUserProvider(config('lighthouse-sanctum.provider'));

        if (! $userProvider) {
            throw new RuntimeException('No UserProvider available.');
        }

        $user = $userProvider->retrieveByCredentials($args);

        if (! $user) {
            throw new AuthenticationException('The provided credentials are incorrect.');
        }

        if (! method_exists($user, 'createToken')) {
            throw new Exception('Missing HasApiTokens trait on "' . get_class($user) . '"');
        }

        return [
            'token' => $user->createToken('default')->plainTextToken,
        ];
    }
}
