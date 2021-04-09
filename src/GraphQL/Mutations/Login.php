<?php

namespace DanielDeWit\LighthouseSanctum\GraphQL\Mutations;

use Exception;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Nuwave\Lighthouse\Exceptions\ValidationException;
use RuntimeException;

class Login
{
    protected ValidationFactory $validator;
    protected AuthManager $authManager;

    public function __construct(
        ValidationFactory $validator,
        AuthManager $authManager
    )
    {
        $this->validator = $validator;
        $this->authManager = $authManager;
    }

    /**
     * @param null $_
     * @param string[] $args
     * @return string[]
     * @throws ValidationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws Exception
     */
    public function __invoke($_, array $args): array
    {
        $validator = $this->validator
            ->make($args, [
                'email'    => 'required|email',
                'password' => 'required',
            ]);

        $userProvider = $this->authManager->createUserProvider(config('lighthouse-sanctum.provider'));

        if (! $userProvider) {
            throw new RuntimeException('No UserProvider available.');
        }

        $user = $userProvider->retrieveByCredentials($validator->validated());

        if (! $user) {
            throw new ValidationException('The provided credentials are incorrect.', $validator);
        }

        if (! method_exists($user, 'createToken')) {
            throw new Exception('Missing HasApiTokens trait on "' . get_class($user) . '"');
        }

        return [
            'token' => $user->createToken('default')->plainTextToken,
        ];
    }
}
