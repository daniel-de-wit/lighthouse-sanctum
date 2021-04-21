<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Tests\Integration\GraphQL\Mutations;

use DanielDeWit\LighthouseSanctum\Tests\Integration\AbstractIntegrationTest;
use DanielDeWit\LighthouseSanctum\Tests\stubs\Users\UserHasApiTokens;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

class ForgotPasswordTest extends AbstractIntegrationTest
{
    /**
     * @test
     */
    public function it_sends_a_reset_password_notification(): void
    {
        Notification::fake();

        /** @var UserHasApiTokens $user */
        $user = UserHasApiTokens::factory()->create([
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
