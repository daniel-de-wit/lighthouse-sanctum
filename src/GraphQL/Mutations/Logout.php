<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\GraphQL\Mutations;

use DanielDeWit\LighthouseSanctum\Exceptions\HasApiTokensException;
use DanielDeWit\LighthouseSanctum\Traits\HasAuthenticatedUser;
use Exception;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Translation\Translator;
use Laravel\Sanctum\Contracts\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;

class Logout
{
    use HasAuthenticatedUser;

    protected AuthFactory $authFactory;
    protected Translator $translator;

    public function __construct(AuthFactory $authFactory, Translator $translator)
    {
        $this->authFactory = $authFactory;
        $this->translator  = $translator;
    }

    /**
     * @param mixed $_
     * @param array<string, mixed> $args
     * @return array<string, string|array>
     * @throws Exception
     */
    public function __invoke($_, array $args): array
    {
        $user = $this->getAuthenticatedUser();

        if (! $user instanceof HasApiTokens) {
            throw new HasApiTokensException($user);
        }

        /** @var PersonalAccessToken $personalAccessToken */
        $personalAccessToken = $user->currentAccessToken();
        $personalAccessToken->delete();

        return [
            'status'  => 'TOKEN_REVOKED',
            'message' => $this->translator->get('Your session has been terminated'),
        ];
    }

    protected function getAuthFactory(): AuthFactory
    {
        return $this->authFactory;
    }
}
