<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\GraphQL\Mutations;

use DanielDeWit\LighthouseSanctum\Enums\LogoutStatus;
use Exception;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Translation\Translator;
use Laravel\Sanctum\PersonalAccessToken;
use RuntimeException;

class Logout
{
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
     * @return array<string, LogoutStatus|string|array|null>
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
            'status'  => LogoutStatus::TOKEN_REVOKED(),
            'message' => $this->translator->get('Your session has been terminated'),
        ];
    }
}
