<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\GraphQL\Mutations;

use DanielDeWit\LighthouseSanctum\Enums\RegisterStatus;
use DanielDeWit\LighthouseSanctum\Traits\CreatesUserProvider;
use Exception;
use Illuminate\Auth\AuthManager;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Config\Repository as Config;

class Register
{
    use CreatesUserProvider;

    protected AuthManager $authManager;
    protected Config $config;

    public function __construct(AuthManager $authManager, Config $config)
    {
        $this->authManager = $authManager;
        $this->config      = $config;
    }

    /**
     * @param mixed $_
     * @param array<string, string> $args
     * @return array<string, RegisterStatus|array|string|null>
     * @throws Exception
     */
    public function __invoke($_, array $args): array
    {
        /** @var EloquentUserProvider $userProvider */
        $userProvider = $this->createUserProvider();

        $user = $userProvider->createModel();
        $user->fill($args);
        $user->save();

        if ($user instanceof MustVerifyEmail) {
            if ($args['verification_url']) {
                VerifyEmail::createUrlUsing(function ($notifiable) use ($args) {
                    $urlWithHash = str_replace(
                        '{{HASH}}',
                        sha1($notifiable->getEmailForVerification()),
                        $args['verification_url'],
                    );

                    return str_replace(
                        '{{ID}}',
                        $notifiable->getKey(),
                        $urlWithHash,
                    );
                });
            }

            $user->sendEmailVerificationNotification();

            return [
                'tokens' => [],
                'status' => RegisterStatus::MUST_VERIFY_EMAIL(),
            ];
        }

        if (! method_exists($user, 'createToken')) {
            throw new Exception('Missing HasApiTokens trait on "' . get_class($user) . '"');
        }

        return [
            'token'  => $user->createToken('default')->plainTextToken,
            'status' => RegisterStatus::SUCCESS(),
        ];
    }

    protected function getAuthManager(): AuthManager
    {
        return $this->authManager;
    }

    protected function getConfig(): Config
    {
        return $this->config;
    }
}
