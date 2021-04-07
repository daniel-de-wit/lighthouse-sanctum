<?php

namespace DanielDeWit\LighthouseSanctum\GraphQL\Mutations;

use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Validator;
use Nuwave\Lighthouse\Exceptions\ValidationException;

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
     * @param $_
     * @param array $args
     * @return MustVerifyEmail|null
     * @throws ValidationException
     */
    public function __invoke($_, array $args)
    {
        $userProvider = $this->authManager->createUserProvider(config('lighthouse-sanctum.provider'));

        /** @var MustVerifyEmail|null $user */
        $user = $userProvider->retrieveById($args['id']);

        \Log::info('user', [$user]);
        \Log::info('hash', [$args['hash']]);

        if (! hash_equals((string) $args['hash'],
            sha1($user->getEmailForVerification()))) {
            throw new ValidationException('The provided id and hash are incorrect.', Validator::make([], []));
        }

        $user->markEmailAsVerified();

        return $user;
    }
}
