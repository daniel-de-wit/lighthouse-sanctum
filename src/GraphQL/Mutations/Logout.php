<?php

namespace DanielDeWit\LighthouseSanctum\GraphQL\Mutations;

use Exception;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Foundation\Auth\User;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;
use RuntimeException;

class Logout
{
    protected AuthFactory $authFactory;

    public function __construct(AuthFactory $authFactory)
    {
        $this->authFactory = $authFactory;
    }

    /**
     * @param null $_
     * @param string[] $args
     * @return array<string, array|string|null>
     * @throws Exception
     */
    public function __invoke($_, array $args): array
    {
        $user = $this->authFactory
            ->guard('sanctum')
            ->user();

        if (! $user) {
            throw new RuntimeException('Unable to detect current user.');
        }

        if (! method_exists($user, 'currentAccessToken')) {
            throw new Exception('Missing HasApiTokens trait on "' . get_class($user) . '"');
        }

        /** @var PersonalAccessToken $personalAccessToken */
        $personalAccessToken = $user->currentAccessToken();
        $personalAccessToken->delete();

        return [
            'status'  => 'TOKEN_REVOKED',
            'message' => __('Your session has been terminated'),
        ];
    }
}
