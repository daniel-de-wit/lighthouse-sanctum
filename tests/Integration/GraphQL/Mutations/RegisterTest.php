<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Tests\Integration\GraphQL\Mutations;

use Carbon\Carbon;
use DanielDeWit\LighthouseSanctum\Tests\Integration\AbstractIntegrationTestCase;
use DanielDeWit\LighthouseSanctum\Tests\stubs\Users\UserHasApiTokens;
use DanielDeWit\LighthouseSanctum\Tests\stubs\Users\UserMustVerifyEmail;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

class RegisterTest extends AbstractIntegrationTestCase
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

        static::assertNotNull($response->json('data.register.token'));
        static::assertSame('SUCCESS', $response->json('data.register.status'));

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
                    verification_url: {
                        url: "https://mysite.com/verify-email/__ID__/__HASH__"
                    }
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
        static::assertSame('MUST_VERIFY_EMAIL', $response->json('data.register.status'));

        $this->assertDatabaseHas('users', [
            'name'  => 'Foo Bar',
            'email' => 'foo@bar.com',
        ]);

        /** @var UserMustVerifyEmail $user */
        $user = UserMustVerifyEmail::first();

        Notification::assertSentTo($user, function (VerifyEmail $notification) use ($user) {
            static::assertIsCallable($notification::$createUrlCallback);

            $url = call_user_func($notification::$createUrlCallback, $user);

            /** @var int|string $id */
            $id   = $user->getKey();
            $hash = sha1('foo@bar.com');

            return $url === "https://mysite.com/verify-email/{$id}/{$hash}";
        });
    }

    /**
     * @test
     */
    public function it_sends_a_signed_email_verification_notification(): void
    {
        Notification::fake();

        Carbon::setTestNow(Carbon::createFromTimestamp(1609477200));

        $this->app['config']->set('auth.providers.users.model', UserMustVerifyEmail::class);
        $this->app['config']->set('lighthouse-sanctum.use_signed_email_verification_url', true);

        $response = $this->graphQL(/** @lang GraphQL */'
            mutation {
                register(input: {
                    name: "Foo Bar",
                    email: "foo@bar.com",
                    password: "supersecret",
                    password_confirmation: "supersecret",
                    verification_url: {
                        url: "https://mysite.com/verify-email/__ID__/__HASH__/__EXPIRES__/__SIGNATURE__"
                    }
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
        static::assertSame('MUST_VERIFY_EMAIL', $response->json('data.register.status'));

        $this->assertDatabaseHas('users', [
            'name'  => 'Foo Bar',
            'email' => 'foo@bar.com',
        ]);

        /** @var UserMustVerifyEmail $user */
        $user = UserMustVerifyEmail::first();

        Notification::assertSentTo($user, function (VerifyEmail $notification) use ($user) {
            static::assertIsCallable($notification::$createUrlCallback);

            $url = call_user_func($notification::$createUrlCallback, $user);

            /** @var int|string $id */
            $id        = $user->getKey();
            $hash      = sha1('foo@bar.com');
            $signature = hash_hmac('sha256', serialize([
                'id'      => $id,
                'hash'    => $hash,
                'expires' => 1609480800,
            ]), $this->getAppKey());

            return $url === "https://mysite.com/verify-email/{$id}/{$hash}/1609480800/{$signature}";
        });
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
        ')->assertGraphQLErrorMessage('String cannot represent a non string value: 12345');
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
        ')->assertGraphQLErrorMessage('String cannot represent a non string value: 12345');
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
                'The input.email field must be a valid email address.',
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
        ')->assertGraphQLErrorMessage('String cannot represent a non string value: 12345');
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
                'The input.password field confirmation does not match.',
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
        ')->assertGraphQLErrorMessage('String cannot represent a non string value: 12345');
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
        ')->assertGraphQLErrorMessage('String cannot represent a non string value: 12345');
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
                'The input.verification url.url field must be a valid URL.',
            );
    }
}
