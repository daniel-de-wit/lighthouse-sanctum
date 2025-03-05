<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Tests\Integration\Services;

use DanielDeWit\LighthouseSanctum\Services\ResetPasswordService;
use DanielDeWit\LighthouseSanctum\Tests\Integration\AbstractIntegrationTestCase;
use DanielDeWit\LighthouseSanctum\Tests\stubs\Users\UserHasApiTokens;
use DanielDeWit\LighthouseSanctum\Tests\stubs\Users\UserMustVerifyEmail;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;

class ResetPasswordServiceTest extends AbstractIntegrationTestCase
{
    protected ResetPasswordService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $dispatcher = Event::fake([PasswordReset::class]);

        /** @var Hasher $hasher */
        $hasher = $this->app->make(Hasher::class);

        $this->service = new ResetPasswordService($hasher, $dispatcher);
    }

    #[Test]
    public function it_transforms_a_reset_password_url(): void
    {
        /** @var UserMustVerifyEmail $user */
        $user = UserMustVerifyEmail::factory()->create([
            'email' => 'user@example.com',
        ]);

        $token = 'token123';

        $url = $this->service->transformUrl($user, $token, 'https://mysite.com/reset-password/__EMAIL__/__TOKEN__');

        static::assertSame('https://mysite.com/reset-password/user%40example.com/token123', $url);
    }

    #[Test]
    public function it_sets_the_reset_password_url(): void
    {
        /** @var UserMustVerifyEmail $user */
        $user = UserMustVerifyEmail::factory()->create([
            'email' => 'user@example.com',
        ]);

        $token = 'token123';

        $this->service->setResetPasswordUrl('https://mysite.com/reset-password/__EMAIL__/__TOKEN__');

        static::assertIsCallable(ResetPassword::$createUrlCallback);

        $url = call_user_func(ResetPassword::$createUrlCallback, $user, $token);

        static::assertSame('https://mysite.com/reset-password/user%40example.com/token123', $url);
    }

    #[Test]
    public function it_resets_a_password(): void
    {
        /** @var Hasher $hasher */
        $hasher = $this->app->make(Hasher::class);

        $password = $hasher->make('some-password');

        /** @var UserHasApiTokens $user */
        $user = UserHasApiTokens::factory()->create([
            'password' => $password,
        ]);

        $this->service->resetPassword($user, 'supersecret');

        static::assertNotSame($password, $user->getAuthPassword());

        Event::assertDispatched(function (PasswordReset $event) use ($user) {
            /** @var Model $eventUser */
            $eventUser = $event->user;

            return $eventUser->is($user);
        });
    }
}
