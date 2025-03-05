<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Tests\Integration\GraphQL\Mutations;

use DanielDeWit\LighthouseSanctum\Tests\Integration\AbstractIntegrationTestCase;
use DanielDeWit\LighthouseSanctum\Tests\stubs\Users\UserHasApiTokens;
use DanielDeWit\LighthouseSanctum\Tests\stubs\Users\UserHasApiTokensIdentifiedByUsername;
use DanielDeWit\LighthouseSanctum\Tests\stubs\Users\UserMustVerifyEmail;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;

class LoginTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function it_logs_a_user_in(): void
    {
        UserHasApiTokens::factory()->create([
            'email'    => 'foo@bar.com',
            'password' => Hash::make('supersecret'),
        ]);

        $this->graphQL(/** @lang GraphQL */ '
            mutation {
                login(input: {
                    email: "foo@bar.com",
                    password: "supersecret"
                }) {
                    token
                }
            }
        ')->assertJsonStructure([
            'data' => [
                'login' => [
                    'token',
                ],
            ],
        ]);
    }

    #[Test]
    public function it_logs_a_user_in_using_custom_user_identifier(): void
    {
        $this->schema = /** @lang GraphQL */ '
            type Query

            type AccessToken {
                token: String!
            }

            input LoginInput {
                username: String!
                password: String!
            }

            extend type Mutation {
                login(input: LoginInput @spread): AccessToken!
                    @field(resolver: "DanielDeWit\\\\LighthouseSanctum\\\\GraphQL\\\\Mutations\\\\Login")
            }
        ';

        $this->setUpTestSchema();

        $this->app['config']->set('auth.providers.users.model', UserHasApiTokensIdentifiedByUsername::class);
        $this->app['config']->set('lighthouse-sanctum.user_identifier_field_name', 'username');

        UserHasApiTokensIdentifiedByUsername::factory()->create([
            'username' => 'john.doe',
            'password' => Hash::make('supersecret'),
        ]);

        $this->graphQL(/** @lang GraphQL */ '
            mutation {
                login(input: {
                    username: "john.doe",
                    password: "supersecret"
                }) {
                    token
                }
            }
        ')->assertJsonStructure([
            'data' => [
                'login' => [
                    'token',
                ],
            ],
        ]);
    }

    #[Test]
    public function it_returns_an_error_if_the_credentials_are_incorrect(): void
    {
        $this->graphQL(/** @lang GraphQL */ '
            mutation {
                login(input: {
                    email: "foo@bar.com",
                    password: "supersecret"
                }) {
                    token
                }
            }
        ')->assertGraphQLErrorMessage('The provided credentials are incorrect.');
    }

    #[Test]
    public function it_returns_an_error_if_the_password_is_incorrect(): void
    {
        UserHasApiTokens::factory()->create([
            'email'    => 'foo@bar.com',
            'password' => Hash::make('supersecret'),
        ]);

        $this->graphQL(/** @lang GraphQL */ '
            mutation {
                login(input: {
                    email: "foo@bar.com",
                    password: "wrong"
                }) {
                    token
                }
            }
        ')->assertGraphQLErrorMessage('The provided credentials are incorrect.');
    }

    #[Test]
    public function it_returns_an_error_if_the_email_is_not_verified(): void
    {
        $this->app['config']->set('auth.providers.users.model', UserMustVerifyEmail::class);

        UserMustVerifyEmail::factory()->create([
            'email'             => 'foo@bar.com',
            'password'          => Hash::make('supersecret'),
            'email_verified_at' => null,
        ]);

        $this->graphQL(/** @lang GraphQL */ '
            mutation {
                login(input: {
                    email: "foo@bar.com",
                    password: "supersecret"
                }) {
                    token
                }
            }
        ')->assertGraphQLErrorMessage('Your email address is not verified.');
    }

    #[Test]
    public function it_returns_an_error_if_the_email_field_is_missing(): void
    {
        $this->graphQL(/** @lang GraphQL */ '
            mutation {
                login(input: {
                    password: "supersecret"
                }) {
                    token
                }
            }
        ')->assertGraphQLErrorMessage('Field LoginInput.email of required type String! was not provided.');
    }

    #[Test]
    public function it_returns_an_error_if_the_email_field_is_not_a_string(): void
    {
        $this->graphQL(/** @lang GraphQL */ '
            mutation {
                login(input: {
                    email: 12345
                    password: "supersecret"
                }) {
                    token
                }
            }
        ')->assertGraphQLErrorMessage('String cannot represent a non string value: 12345');
    }

    #[Test]
    public function it_returns_an_error_if_the_email_field_is_not_an_email(): void
    {
        $this->graphQL(/** @lang GraphQL */ '
            mutation {
                login(input: {
                    email: "foobar"
                    password: "supersecret"
                }) {
                    token
                }
            }
        ')
            ->assertGraphQLErrorMessage('Validation failed for the field [login].')
            ->assertGraphQLValidationError(
                'input.email',
                'The input.email field must be a valid email address.',
            );
    }

    #[Test]
    public function it_returns_an_error_if_the_password_field_is_missing(): void
    {
        $this->graphQL(/** @lang GraphQL */ '
            mutation {
                login(input: {
                    email: "foo@bar.com"
                }) {
                    token
                }
            }
        ')->assertGraphQLErrorMessage('Field LoginInput.password of required type String! was not provided.');
    }

    #[Test]
    public function it_returns_an_error_if_the_password_field_is_not_a_string(): void
    {
        $this->graphQL(/** @lang GraphQL */ '
            mutation {
                login(input: {
                    email: "foobar"
                    password: 12345
                }) {
                    token
                }
            }
        ')->assertGraphQLErrorMessage('String cannot represent a non string value: 12345');
    }
}
