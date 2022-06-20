<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Tests\Integration\GraphQL\Mutations;

use DanielDeWit\LighthouseSanctum\Tests\Integration\AbstractIntegrationTest;
use DanielDeWit\LighthouseSanctum\Tests\stubs\Users\UserHasApiTokens;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

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

        Notification::assertSentTo($user, function (ResetPassword $notification) use ($user) {
            /** @phpstan-ignore-next-line */
            $url = call_user_func($notification::$createUrlCallback, $user, $notification->token);

            return $url === "https://my-front-end.com/reset-password?email=john.doe@gmail.com&token={$notification->token}";
        });
    }

    /**
     * @test
     */
    public function it_fails_silently_when_the_email_is_not_found(): void
    {
        Notification::fake();

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

        Notification::assertNothingSent();
    }

    /**
     * @test
     */
    public function it_returns_an_error_if_the_email_field_is_missing(): void
    {
        $this->graphQL(/** @lang GraphQL */'
            mutation {
                forgotPassword(input: {
                    reset_password_url: {
                      url: "https://mysite.com/reset-password?email=__EMAIL__&token=__TOKEN__"
                    }
                }) {
                    status
                    message
                }
            }
        ')->assertGraphQLErrorMessage('Field ForgotPasswordInput.email of required type String! was not provided.');
    }

    /**
     * @test
     */
    public function it_returns_an_error_if_the_email_field_is_not_a_string(): void
    {
        $this->graphQL(/** @lang GraphQL */'
            mutation {
                forgotPassword(input: {
                    email: 12345
                    reset_password_url: {
                      url: "https://mysite.com/reset-password?email=__EMAIL__&token=__TOKEN__"
                    }
                }) {
                    status
                    message
                }
            }
        ')->assertGraphQLErrorMessage('Field "forgotPassword" argument "input" requires type String!, found 12345.');
    }

    /**
     * @test
     */
    public function it_returns_an_error_if_the_email_field_is_not_an_email(): void
    {
        $this->graphQL(/** @lang GraphQL */'
            mutation {
                forgotPassword(input: {
                    email: "foobar"
                    reset_password_url: {
                      url: "https://mysite.com/reset-password?email=__EMAIL__&token=__TOKEN__"
                    }
                }) {
                    status
                    message
                }
            }
        ')
            ->assertGraphQLErrorMessage('Validation failed for the field [forgotPassword].')
            ->assertGraphQLValidationError(
                'input.email',
                'The input.email must be a valid email address.',
            );
    }

    /**
     * @test
     */
    public function it_returns_an_error_if_the_reset_password_url_field_is_missing(): void
    {
        $this->graphQL(/** @lang GraphQL */'
            mutation {
                forgotPassword(input: {
                    email: "foo@bar.com"
                    reset_password_url: {}
                }) {
                    status
                    message
                }
            }
        ')->assertGraphQLErrorMessage('Field ResetPasswordUrlInput.url of required type String! was not provided.');
    }

    /**
     * @test
     */
    public function it_returns_an_error_if_the_reset_password_url_field_is_not_a_string(): void
    {
        $this->graphQL(/** @lang GraphQL */'
            mutation {
                forgotPassword(input: {
                    email: "foo@bar.com"
                    reset_password_url: {
                      url: 12345
                    }
                }) {
                    status
                    message
                }
            }
        ')->assertGraphQLErrorMessage('Field "forgotPassword" argument "input" requires type String!, found 12345.');
    }

    /**
     * @test
     */
    public function it_returns_an_error_if_the_reset_password_url_field_is_not_a_url(): void
    {
        $this->graphQL(/** @lang GraphQL */'
            mutation {
                forgotPassword(input: {
                    email: "foo@bar.com"
                    reset_password_url: {
                      url: "google"
                    }
                }) {
                    status
                    message
                }
            }
        ')
            ->assertGraphQLErrorMessage('Validation failed for the field [forgotPassword].')
            ->assertGraphQLValidationError(
                'input.reset_password_url.url',
                'The input.reset password url.url must be a valid URL.',
            );
    }
}
