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

    public function __construct(
        protected AuthFactory $authFactory,
        protected Translator $translator,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, string>
     *
     * @throws Exception
     */
    public function __invoke(mixed $_, array $args): array
    {
        $user = $this->getAuthenticatedUser();

        if (! $user instanceof HasApiTokens) {
            throw new HasApiTokensException($user);
        }

        /** @var PersonalAccessToken $personalAccessToken */
        $personalAccessToken = $user->currentAccessToken();
        $personalAccessToken->delete();

        /** @var string $message */
        $message = $this->translator->get('Your session has been terminated');

        return [
            'status'  => 'TOKEN_REVOKED',
            'message' => $message,
        ];
    }

    protected function getAuthFactory(): AuthFactory
    {
        return $this->authFactory;
    }
}
