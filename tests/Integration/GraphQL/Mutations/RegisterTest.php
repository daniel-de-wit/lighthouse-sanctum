<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Tests\Integration\GraphQL\Mutations;

use DanielDeWit\LighthouseSanctum\Enums\RegisterStatus;
use DanielDeWit\LighthouseSanctum\Tests\Integration\AbstractIntegrationTest;
use DanielDeWit\LighthouseSanctum\Tests\stubs\Users\UserHasApiTokens;
use DanielDeWit\LighthouseSanctum\Tests\stubs\Users\UserMustVerifyEmail;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

class RegisterTest extends AbstractIntegrationTest
{
    /**
     * @test
     */
    public function it_registers_a_user(): void
    {
        $response = $this->graphQL(/** @lang GraphQL */'
            mutation {
                register(input: {
                    name: "Foo Bar",
                    email: "foo@bar.com",
                    password: "supersecret",
                    password_confirmation: "supersecret",
                }) {
                    token
                    status
                }
            }
        ')->assertJsonStructure([
            'data' => [
                'register' => [
                    'token',
                    'status',
                ],
            ],
        ]);

        static::assertTrue(RegisterStatus::SUCCESS()->is($response->json('data.register.status')));

        $this->assertDatabaseHas('users', [
            'name'  => 'Foo Bar',
            'email' => 'foo@bar.com',
        ]);
    }

    /**
     * @test
     */
    public function it_sends_an_email_verification_notification(): void
    {
        Notification::fake();

        $this->app['config']->set('auth.providers.users.model', UserMustVerifyEmail::class);

        $response = $this->graphQL(/** @lang GraphQL */'
            mutation {
                register(input: {
                    name: "Foo Bar",
                    email: "foo@bar.com",
                    password: "supersecret",
                    password_confirmation: "supersecret",
                }) {
                    token
                    status
                }
            }
        ')->assertJsonStructure([
            'data' => [
                'register' => [
                    'token',
                    'status',
                ],
            ],
        ]);

        static::assertNull($response->json('data.register.token'));
        static::assertTrue(RegisterStatus::MUST_VERIFY_EMAIL()->is($response->json('data.register.status')));

        $this->assertDatabaseHas('users', [
            'name'  => 'Foo Bar',
            'email' => 'foo@bar.com',
        ]);

        $user = UserMustVerifyEmail::first();

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    /**
     * @test
     */
    public function it_returns_an_error_if_the_name_field_is_missing(): void
    {
        $this->graphQL(/** @lang GraphQL */'
            mutation {
                register(input: {
                    email: "foo@bar.com",
                    password: "supersecret",
                    password_confirmation: "supersecret",
                }) {
                    token
                    status
                }
            }
        ')->assertGraphQLErrorMessage('Field RegisterInput.name of required type String! was not provided.');
    }

    /**
     * @test
     */
    public function it_returns_an_error_if_the_name_field_is_not_a_string(): void
    {
        $this->graphQL(/** @lang GraphQL */'
            mutation {
                register(input: {
                    name: 12345,
                    email: "foo@bar.com",
                    password: "supersecret",
                    password_confirmation: "supersecret",
                }) {
                    token
                    status
                }
            }
        ')->assertGraphQLErrorMessage('Field "register" argument "input" requires type String!, found 12345.');
    }

    /**
     * @test
     */
    public function it_returns_an_error_if_the_email_field_is_missing(): void
    {
        $this->graphQL(/** @lang GraphQL */'
            mutation {
                register(input: {
                    name: "Foo Bar",
                    password: "supersecret",
                    password_confirmation: "supersecret",
                }) {
                    token
                    status
                }
            }
        ')->assertGraphQLErrorMessage('Field RegisterInput.email of required type String! was not provided.');
    }

    /**
     * @test
     */
    public function it_returns_an_error_if_the_email_field_is_not_a_string(): void
    {
        $this->graphQL(/** @lang GraphQL */'
            mutation {
                register(input: {
                    name: "Foo Bar",
                    email: 12345,
                    password: "supersecret",
                    password_confirmation: "supersecret",
                }) {
                    token
                    status
                }
            }
        ')->assertGraphQLErrorMessage('Field "register" argument "input" requires type String!, found 12345.');
    }

    /**
     * @test
     */
    public function it_returns_an_error_if_the_email_field_is_not_an_email(): void
    {
        $this->graphQL(/** @lang GraphQL */'
            mutation {
                register(input: {
                    name: "Foo Bar",
                    email: "foobar",
                    password: "supersecret",
                    password_confirmation: "supersecret",
                }) {
                    token
                    status
                }
            }
        ')
            ->assertGraphQLErrorMessage('Validation failed for the field [register].')
            ->assertGraphQLValidationError(
                'input.email',
                'The input.email must be a valid email address.',
            );
    }

    /**
     * @test
     */
    public function it_returns_an_error_if_the_email_is_not_unique(): void
    {
        UserHasApiTokens::factory()->create([
            'email' => 'foo@bar.com',
        ]);

        $this->graphQL(/** @lang GraphQL */'
            mutation {
                register(input: {
                    name: "Foo Bar",
                    email: "foo@bar.com",
                    password: "supersecret",
                    password_confirmation: "supersecret",
                }) {
                    token
                    status
                }
            }
        ')
            ->assertGraphQLErrorMessage('Validation failed for the field [register].')
            ->assertGraphQLValidationError(
                'input.email',
                'The input.email has already been taken.',
            );
    }

    /**
     * @test
     */
    public function it_returns_an_error_if_the_password_field_is_missing(): void
    {
        $this->graphQL(/** @lang GraphQL */'
            mutation {
                register(input: {
                    name: "Foo Bar",
                    email: "foo@bar.com",
                    password_confirmation: "supersecret",
                }) {
                    token
                    status
                }
            }
        ')->assertGraphQLErrorMessage('Field RegisterInput.password of required type String! was not provided.');
    }

    /**
     * @test
     */
    public function it_returns_an_error_if_the_password_field_is_not_a_string(): void
    {
        $this->graphQL(/** @lang GraphQL */'
            mutation {
                register(input: {
                    name: "Foo Bar",
                    email: "foo@bar.com",
                    password: 12345,
                    password_confirmation: "supersecret",
                }) {
                    token
                    status
                }
            }
        ')->assertGraphQLErrorMessage('Field "register" argument "input" requires type String!, found 12345.');
    }

    /**
     * @test
     */
    public function it_returns_an_error_if_the_password_field_is_not_confirmed(): void
    {
        $this->graphQL(/** @lang GraphQL */'
            mutation {
                register(input: {
                    name: "Foo Bar",
                    email: "foo@bar.com",
                    password: "supersecret",
                    password_confirmation: "somethingelse",
                }) {
                    token
                    status
                }
            }
        ')
            ->assertGraphQLErrorMessage('Validation failed for the field [register].')
            ->assertGraphQLValidationError(
                'input.password',
                'The input.password confirmation does not match.',
            );
    }

    /**
     * @test
     */
    public function it_returns_an_error_if_the_password_confirmation_field_is_missing(): void
    {
        $this->graphQL(/** @lang GraphQL */'
            mutation {
                register(input: {
                    name: "Foo Bar",
                    email: "foo@bar.com",
                    password: "supersecret",
                }) {
                    token
                    status
                }
            }
        ')->assertGraphQLErrorMessage('Field RegisterInput.password_confirmation of required type String! was not provided.');
    }

    /**
     * @test
     */
    public function it_returns_an_error_if_the_password_confirmation_field_is_not_a_string(): void
    {
        $this->graphQL(/** @lang GraphQL */'
            mutation {
                register(input: {
                    name: "Foo Bar",
                    email: "foo@bar.com",
                    password: "supersecret",
                    password_confirmation: 12345,
                }) {
                    token
                    status
                }
            }
        ')->assertGraphQLErrorMessage('Field "register" argument "input" requires type String!, found 12345.');
    }

    /**
     * @test
     */
    public function it_returns_an_error_if_the_verification_url_field_is_missing(): void
    {
        $this->graphQL(/** @lang GraphQL */'
            mutation {
                register(input: {
                    name: "Foo Bar",
                    email: "foo@bar.com",
                    password: "supersecret",
                    password_confirmation: "supersecret",
                    verification_url: {}
                }) {
                    token
                    status
                }
            }
        ')->assertGraphQLErrorMessage('Field VerificationUrlInput.url of required type String! was not provided.');
    }

    /**
     * @test
     */
    public function it_returns_an_error_if_the_verification_url_field_is_not_a_string(): void
    {
        $this->graphQL(/** @lang GraphQL */'
            mutation {
                register(input: {
                    name: "Foo Bar",
                    email: "foo@bar.com",
                    password: "supersecret",
                    password_confirmation: "supersecret",
                    verification_url: {
                        url: 12345
                    }
                }) {
                    token
                    status
                }
            }
        ')->assertGraphQLErrorMessage('Field "register" argument "input" requires type String!, found 12345.');
    }

    /**
     * @test
     */
    public function it_returns_an_error_if_the_verification_url_field_is_not_a_url(): void
    {
        $this->graphQL(/** @lang GraphQL */'
            mutation {
                register(input: {
                    name: "Foo Bar",
                    email: "foo@bar.com",
                    password: "supersecret",
                    password_confirmation: "supersecret",
                    verification_url: {
                        url: "google"
                    }
                }) {
                    token
                    status
                }
            }
        ')
            ->assertGraphQLErrorMessage('Validation failed for the field [register].')
            ->assertGraphQLValidationError(
                'input.verification_url.url',
                'The input.verification url.url format is invalid.',
            );
    }
}
