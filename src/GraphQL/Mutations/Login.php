<?php

namespace DanielDeWit\LighthouseSanctum\GraphQL\Mutations;

use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Laravel\Sanctum\HasApiTokens;
use Nuwave\Lighthouse\Exceptions\ValidationException;

class Login
{
    protected ValidationFactory $validator;
    protected AuthManager $authManager;

    public function __construct(
        ValidationFactory $validator,
        AuthManager $authManager
    ) {
        $this->validator = $validator;
        $this->authManager = $authManager;
    }

    /**
     * @param $_
     * @param array $args
     * @return array
     * @throws ValidationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function __invoke($_, array $args): array
    {
        $validator = $this->validator
            ->make($args, [
                'email'    => 'required|email',
                'password' => 'required',
            ]);

        $userProvider = $this->authManager->createUserProvider(config('lighthouse-sanctum.provider'));

        /** @var Authenticatable|HasApiTokens $user */
        $user = $userProvider->retrieveByCredentials($validator->validated());

        if (! $user) {
            throw new ValidationException('The provided credentials are incorrect.', $validator);
        }

        return [
            'token' => $user->createToken('default')->plainTextToken,
        ];
    }
}
