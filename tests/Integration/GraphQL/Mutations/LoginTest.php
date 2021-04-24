<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Tests\Integration\GraphQL\Mutations;

use DanielDeWit\LighthouseSanctum\Tests\Integration\AbstractIntegrationTest;
use DanielDeWit\LighthouseSanctum\Tests\stubs\Users\UserHasApiTokens;
use Illuminate\Support\Facades\Hash;

class LoginTest extends AbstractIntegrationTest
{
    /**
     * @test
     */
    public function it_logs_a_user_in(): void
    {
        UserHasApiTokens::factory()->create([
            'email'    => 'foo@bar.com',
            'password' => Hash::make('supersecret'),
        ]);

        $this->graphQL(/** @lang GraphQL */'
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

    /**
     * @test
     */
    public function it_returns_an_error_if_the_credentials_are_incorrect(): void
    {
        $this->graphQL(/** @lang GraphQL */'
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

    /**
     * @test
     */
    public function it_returns_an_error_if_the_email_field_is_missing(): void
    {
        $this->graphQL(/** @lang GraphQL */'
            mutation {
                login(input: {
                    password: "supersecret"
                }) {
                    token
                }
            }
        ')->assertGraphQLErrorMessage('Field LoginInput.email of required type String! was not provided.');
    }

    /**
     * @test
     */
    public function it_returns_an_error_if_the_email_field_is_not_a_string(): void
    {
        $this->graphQL(/** @lang GraphQL */'
            mutation {
                login(input: {
                    email: 12345
                    password: "supersecret"
                }) {
                    token
                }
            }
        ')->assertGraphQLErrorMessage('Field "login" argument "input" requires type String!, found 12345.');
    }

    /**
     * @test
     */
    public function it_returns_an_error_if_the_email_field_is_not_an_email(): void
    {
        $this->graphQL(/** @lang GraphQL */'
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
                'The input.email must be a valid email address.',
            );
    }

    /**
     * @test
     */
    public function it_returns_an_error_if_the_password_field_is_missing(): void
    {
        $this->graphQL(/** @lang GraphQL */'
            mutation {
                login(input: {
                    email: "foo@bar.com"
                }) {
                    token
                }
            }
        ')->assertGraphQLErrorMessage('Field LoginInput.password of required type String! was not provided.');
    }

    /**
     * @test
     */
    public function it_returns_an_error_if_the_password_field_is_not_a_string(): void
    {
        $this->graphQL(/** @lang GraphQL */'
            mutation {
                login(input: {
                    email: "foobar"
                    password: 12345
                }) {
                    token
                }
            }
        ')->assertGraphQLErrorMessage('Field "login" argument "input" requires type String!, found 12345.');
    }
}
