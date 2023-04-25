<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Tests\Integration\GraphQL\Mutations;

use DanielDeWit\LighthouseSanctum\Tests\Integration\AbstractIntegrationTestCase;
use DanielDeWit\LighthouseSanctum\Tests\stubs\Users\UserHasApiTokens;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;

class ResetPasswordTest extends AbstractIntegrationTestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function it_resets_a_password(): void
    {
        Event::fake([PasswordReset::class]);

        /** @var UserHasApiTokens $user */
        $user = UserHasApiTokens::factory()->create([
            'email' => 'foo@bar.com',
        ]);

        $token = '';

        /** @var PasswordBroker $passwordBroker */
        $passwordBroker = $this->app->make(PasswordBroker::class);
        $passwordBroker->sendResetLink(
            ['email' => 'foo@bar.com'],
            function ($user, $resetToken) use (&$token) {
                $token = $resetToken;
            }
        );

        $this->graphQL(/** @lang GraphQL */ '
            mutation {
                resetPassword(input: {
                    email: "foo@bar.com",
                    token: "'.$token.'",
                    password: "supersecret",
                    password_confirmation: "supersecret"
                }) {
                    status
                    message
                }
            }
        ')->assertJson([
            'data' => [
                'resetPassword' => [
                    'status'  => 'PASSWORD_RESET',
                    'message' => 'Your password has been reset.',
                ],
            ],
        ]);

        Event::assertDispatched(function (PasswordReset $event) use ($user) {
            /** @var Model $eventUser */
            $eventUser = $event->user;

            return $eventUser->is($user);
        });
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_an_error_if_the_email_field_is_missing(): void
    {
        $this->graphQL(/** @lang GraphQL */ '
            mutation {
                resetPassword(input: {
                    token: "1234567890",
                    password: "supersecret",
                    password_confirmation: "supersecret"
                }) {
                    status
                    message
                }
            }
        ')->assertGraphQLErrorMessage('Field ResetPasswordInput.email of required type String! was not provided.');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_an_error_if_the_email_field_is_not_a_string(): void
    {
        $this->graphQL(/** @lang GraphQL */ '
            mutation {
                resetPassword(input: {
                    email: 12345,
                    token: "1234567890",
                    password: "supersecret",
                    password_confirmation: "supersecret"
                }) {
                    status
                    message
                }
            }
        ')->assertGraphQLErrorMessage('String cannot represent a non string value: 12345');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_an_error_if_the_email_field_is_not_an_email(): void
    {
        $this->graphQL(/** @lang GraphQL */ '
            mutation {
                resetPassword(input: {
                    email: "foobar",
                    token: "1234567890",
                    password: "supersecret",
                    password_confirmation: "supersecret"
                }) {
                    status
                    message
                }
            }
        ')
            ->assertGraphQLErrorMessage('Validation failed for the field [resetPassword].')
            ->assertGraphQLValidationError(
                'input.email',
                'The input.email field must be a valid email address.',
            );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_an_error_if_the_email_is_not_found(): void
    {
        $this->graphQL(/** @lang GraphQL */ '
            mutation {
                resetPassword(input: {
                    email: "foo@bar.com",
                    token: "1234567890",
                    password: "supersecret",
                    password_confirmation: "supersecret"
                }) {
                    status
                    message
                }
            }
        ')
            ->assertGraphQLErrorMessage('Validation failed for the field [resetPassword].')
            ->assertGraphQLValidationError('input.email', "We can't find a user with that email address.");
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_an_error_if_the_token_field_is_missing(): void
    {
        $this->graphQL(/** @lang GraphQL */ '
            mutation {
                resetPassword(input: {
                    email: "foo@bar.com",
                    password: "supersecret",
                    password_confirmation: "supersecret"
                }) {
                    status
                    message
                }
            }
        ')->assertGraphQLErrorMessage('Field ResetPasswordInput.token of required type String! was not provided.');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_an_error_if_the_token_field_is_not_a_string(): void
    {
        $this->graphQL(/** @lang GraphQL */ '
            mutation {
                resetPassword(input: {
                    email: "foo@bar.com",
                    token: 12345,
                    password: "supersecret",
                    password_confirmation: "supersecret"
                }) {
                    status
                    message
                }
            }
        ')->assertGraphQLErrorMessage('String cannot represent a non string value: 12345');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_an_error_if_the_token_is_not_found(): void
    {
        UserHasApiTokens::factory()->create([
            'email' => 'foo@bar.com',
        ]);

        $this->graphQL(/** @lang GraphQL */ '
            mutation {
                resetPassword(input: {
                    email: "foo@bar.com",
                    token: "1234567890",
                    password: "supersecret",
                    password_confirmation: "supersecret"
                }) {
                    status
                    message
                }
            }
        ')
            ->assertGraphQLErrorMessage('Validation failed for the field [resetPassword].')
            ->assertGraphQLValidationError('input.token', 'This password reset token is invalid.');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_an_error_if_the_password_field_is_missing(): void
    {
        $this->graphQL(/** @lang GraphQL */ '
            mutation {
                resetPassword(input: {
                    email: "foo@bar.com",
                    token: "1234567890",
                    password_confirmation: "supersecret"
                }) {
                    status
                    message
                }
            }
        ')->assertGraphQLErrorMessage('Field ResetPasswordInput.password of required type String! was not provided.');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_an_error_if_the_password_field_is_not_a_string(): void
    {
        $this->graphQL(/** @lang GraphQL */ '
            mutation {
                resetPassword(input: {
                    email: "foo@bar.com",
                    token: "1234567890",
                    password: 12345,
                    password_confirmation: "supersecret"
                }) {
                    status
                    message
                }
            }
        ')->assertGraphQLErrorMessage('String cannot represent a non string value: 12345');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_an_error_if_the_password_field_is_not_confirmed(): void
    {
        $this->graphQL(/** @lang GraphQL */ '
            mutation {
                resetPassword(input: {
                    email: "foo@bar.com",
                    token: "1234567890",
                    password: "supersecret",
                    password_confirmation: "somethingelse"
                }) {
                    status
                    message
                }
            }
        ')
            ->assertGraphQLErrorMessage('Validation failed for the field [resetPassword].')
            ->assertGraphQLValidationError(
                'input.password',
                'The input.password field confirmation does not match.',
            );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_an_error_if_the_password_confirmation_field_is_missing(): void
    {
        $this->graphQL(/** @lang GraphQL */ '
            mutation {
                resetPassword(input: {
                    email: "foo@bar.com",
                    token: "1234567890",
                    password: "supersecret",
                }) {
                    status
                    message
                }
            }
        ')->assertGraphQLErrorMessage('Field ResetPasswordInput.password_confirmation of required type String! was not provided.');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_an_error_if_the_password_confirmation_field_is_not_a_string(): void
    {
        $this->graphQL(/** @lang GraphQL */ '
            mutation {
                resetPassword(input: {
                    email: "foo@bar.com",
                    token: "1234567890",
                    password: "supersecret",
                    password_confirmation: 12345
                }) {
                    status
                    message
                }
            }
        ')->assertGraphQLErrorMessage('String cannot represent a non string value: 12345');
    }
}
