<?php

namespace DanielDeWit\LighthouseSanctum\GraphQL\Mutations;

use Exception;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Validator;
use Nuwave\Lighthouse\Exceptions\ValidationException;
use RuntimeException;

class VerifyEmail
{
    protected AuthManager $authManager;

    public function __construct(
        AuthManager $authManager
    )
    {
        $this->authManager = $authManager;
    }

    /**
     * @param null $_
     * @param string[] $args
     * @return MustVerifyEmail
     * @throws ValidationException
     * @throws Exception
     */
    public function __invoke($_, array $args): MustVerifyEmail
    {
        $userProvider = $this->authManager->createUserProvider(config('lighthouse-sanctum.provider'));

        if (! $userProvider) {
            throw new RuntimeException('No UserProvider available.');
        }

        $user = $userProvider->retrieveById($args['id']);

        if (! $user instanceof MustVerifyEmail) {
            throw new RuntimeException('User not instance of MustVerifyEmail');
        }

        if (! hash_equals($args['hash'],
            sha1($user->getEmailForVerification()))) {
            throw new ValidationException('The provided id and hash are incorrect.', Validator::make([], []));
        }

        $user->markEmailAsVerified();

        return $user;
    }
}
