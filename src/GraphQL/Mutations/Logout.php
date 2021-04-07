<?php

namespace DanielDeWit\LighthouseSanctum\GraphQL\Mutations;

use Exception;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Foundation\Auth\User;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;

class Logout
{
    protected AuthFactory $authFactory;

    public function __construct(AuthFactory $authFactory)
    {
        $this->authFactory = $authFactory;
    }

    /**
     * @param null $_
     * @param array $args
     * @return array
     * @throws Exception
     */
    public function __invoke($_, array $args): array
    {
        /**
         * @var HasApiTokens|User $user
         */
        $user = $this->authFactory
            ->guard('sanctum')
            ->user();

        /** @var PersonalAccessToken $personalAccessToken */
        $personalAccessToken = $user->currentAccessToken();
        $personalAccessToken->delete();

        return [
            'status'  => 'TOKEN_REVOKED',
            'message' => __('Your session has been terminated'),
        ];
    }
}
