<?php

namespace DanielDeWit\LighthouseSanctum\GraphQL\Mutations;

use DanielDeWit\LighthouseSanctum\Enums\RegisterStatus;
use Exception;
use Illuminate\Auth\AuthManager;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class Register
{
    protected AuthManager $authManager;

    public function __construct(AuthManager $authManager)
    {
        $this->authManager = $authManager;
    }

    /**
     * @param null $_
     * @param string[] $args
     * @return array<string, array|string|null>
     * @throws Exception
     */
    public function __invoke($_, array $args): array
    {
        /** @var EloquentUserProvider $userProvider */
        $userProvider = $this->authManager->createUserProvider(config('lighthouse-sanctum.provider'));

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
                'status' => RegisterStatus::MUST_VERIFY_EMAIL,
            ];
        }

        if (! method_exists($user, 'createToken')) {
            throw new Exception('Missing HasApiTokens trait on "' . get_class($user) . '"');
        }

        return [
            'token'  => $user->createToken('default')->plainTextToken,
            'status' => RegisterStatus::SUCCESS,
        ];
    }
}
