<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Tests\Integration\GraphQL\Mutations;

use DanielDeWit\LighthouseSanctum\Contracts\Services\ResetPasswordServiceInterface;
use DanielDeWit\LighthouseSanctum\GraphQL\Mutations\ForgotPassword;
use DanielDeWit\LighthouseSanctum\Tests\Integration\AbstractIntegrationTest;
use DanielDeWit\LighthouseSanctum\Tests\stubs\Users\UserCanResetPassword;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

class ForgotPasswordTest extends AbstractIntegrationTest
{
    protected ForgotPassword $mutation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('auth.providers', [
            'users' => [
                'driver' => 'eloquent',
                'model'  => UserCanResetPassword::class,
            ],
        ]);

        $this->mutation = new ForgotPassword(
            $this->app->make(PasswordBroker::class),
            $this->app->make(ResetPasswordServiceInterface::class),
            $this->app->make(Translator::class),
        );
    }

    /**
     * @test
     */
    public function it_sends_a_reset_password_notification(): void
    {
        Notification::fake();

        /** @var UserCanResetPassword $user */
        $user = UserCanResetPassword::factory()->create([
            'email' => 'john.doe@gmail.com',
        ]);

        $user->notify(new ResetPasswordNotification('bla'));

        Sanctum::actingAs($user);

        $this->graphQL(/** @lang GraphQL */ '
            mutation {
                forgotPassword(input: {
                    email: "john.doe@gmail.com"
                    reset_password_url: {
                      url: "https://my-front-end.com/reset-password?email=__EMAIL__&token=__TOKEN__"
                    }
                }) {
                    status
                    message
                }
            }
        ')->assertJson([
            'data' => [
                'forgotPassword' => [
                    'status'  => 'EMAIL_SENT',
                    'message' => 'An email has been sent',
                ],
            ],
        ]);

        Notification::assertSentTo($user, ResetPassword::class);
    }
}
