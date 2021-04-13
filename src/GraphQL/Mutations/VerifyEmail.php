<?php

namespace DanielDeWit\LighthouseSanctum\GraphQL\Mutations;

use DanielDeWit\LighthouseSanctum\Enums\EmailVerificationStatus;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Validator;
use Nuwave\Lighthouse\Exceptions\ValidationException;
use RuntimeException;

class VerifyEmail
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
     * @throws ValidationException
     */
    public function __invoke($_, array $args): array
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

        return [
            'status' => EmailVerificationStatus::VERIFIED,
        ];
    }
}
