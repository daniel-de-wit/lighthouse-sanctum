<?php

namespace DanielDeWit\LighthouseSanctum\GraphQL\Mutations;

use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\AuthManager;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class Register
{
    protected AuthManager $authManager;

    public function __construct(AuthManager $authManager)
    {
        $this->authManager = $authManager;
    }

    /**
     * @param $_
     * @param array $args
     * @return array
     */
    public function __invoke($_, array $args): array
    {
        /** @var EloquentUserProvider $userProvider */
        $userProvider = $this->authManager->createUserProvider(config('lighthouse-sanctum.provider'));

        /** @var Authenticatable|Model|HasApiTokens $user */
        $user = $userProvider->createModel();
        $user->fill($args);
        $user->save();

        if ($user instanceof MustVerifyEmail) {
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

            $user->sendEmailVerificationNotification();

            return [
                'tokens' => [],
                'status' => 'MUST_VERIFY_EMAIL',
            ];
        }

        return [
            'token'  => $user->createToken('default')->plainTextToken,
            'status' => 'SUCCESS',
        ];
    }
}
