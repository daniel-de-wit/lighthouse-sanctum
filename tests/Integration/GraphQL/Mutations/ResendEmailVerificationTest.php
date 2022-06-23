<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Tests\Integration\GraphQL\Mutations;

use Carbon\Carbon;
use DanielDeWit\LighthouseSanctum\Tests\Integration\AbstractIntegrationTest;
use DanielDeWit\LighthouseSanctum\Tests\stubs\Users\UserHasApiTokens;
use DanielDeWit\LighthouseSanctum\Tests\stubs\Users\UserMustVerifyEmail;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

class ResendEmailVerificationTest extends AbstractIntegrationTest
{
    /**
     * @test
     */
    public function it_resends_an_email_verification_notification(): void
    {
        Notification::fake();

        $this->app['config']->set('auth.providers.users.model', UserMustVerifyEmail::class);

        $user = UserMustVerifyEmail::factory()->create([
            'id'                => 123,
            'email'             => 'foo@bar.com',
            'email_verified_at' => null,
        ]);

        $response = $this->graphQL(/** @lang GraphQL */'
            mutation {
                resendEmailVerification(input: {
                    email: "foo@bar.com",
                    verification_url: {
                        url: "https://mysite.com/verify-email/__ID__/__HASH__"
                    }
                }) {
                    status
                }
            }
        ')->assertJsonStructure([
            'data' => [
                'resendEmailVerification' => [
                    'status',
                ],
            ],
        ]);

        static::assertSame('EMAIL_SENT', $response->json('data.resendEmailVerification.status'));

        Notification::assertSentTo($user, function (VerifyEmail $notification) use ($user) {
            static::assertIsCallable($notification::$createUrlCallback);

            $url = call_user_func($notification::$createUrlCallback, $user);

            $hash = sha1('foo@bar.com');

            return $url === "https://mysite.com/verify-email/123/{$hash}";
        });
    }

    /**
     * @test
     */
    public function it_resends_a_signed_email_verification_notification(): void
    {
        Notification::fake();

        Carbon::setTestNow(Carbon::createFromTimestamp(1609477200));

        $this->app['config']->set('auth.providers.users.model', UserMustVerifyEmail::class);
        $this->app['config']->set('lighthouse-sanctum.use_signed_email_verification_url', true);

        $user = UserMustVerifyEmail::factory()->create([
            'id'                => 123,
            'email'             => 'foo@bar.com',
            'email_verified_at' => null,
        ]);

        $response = $this->graphQL(/** @lang GraphQL */'
            mutation {
                resendEmailVerification(input: {
                    email: "foo@bar.com",
                    verification_url: {
                        url: "https://mysite.com/verify-email/__ID__/__HASH__/__EXPIRES__/__SIGNATURE__"
                    }
                }) {
                    status
                }
            }
        ')->assertJsonStructure([
            'data' => [
                'resendEmailVerification' => [
                    'status',
                ],
            ],
        ]);

        static::assertSame('EMAIL_SENT', $response->json('data.resendEmailVerification.status'));

        Notification::assertSentTo($user, function (VerifyEmail $notification) use ($user) {
            static::assertIsCallable($notification::$createUrlCallback);

            $url = call_user_func($notification::$createUrlCallback, $user);

            $hash      = sha1('foo@bar.com');
            $signature = hash_hmac('sha256', serialize([
                'id'      => 123,
                'hash'    => $hash,
                'expires' => 1609480800,
            ]), $this->getAppKey());

            return $url === "https://mysite.com/verify-email/123/{$hash}/1609480800/{$signature}";
        });
    }

    /**
     * @test
     */
    public function it_does_not_resend_an_email_verification_notification_if_the_email_does_not_exist(): void
    {
        Notification::fake();

        $this->app['config']->set('auth.providers.users.model', UserMustVerifyEmail::class);

        $response = $this->graphQL(/** @lang GraphQL */'
            mutation {
                resendEmailVerification(input: {
                    email: "foo@bar.com",
                }) {
                    status
                }
            }
        ')->assertJsonStructure([
            'data' => [
                'resendEmailVerification' => [
                    'status',
                ],
            ],
        ]);

        static::assertSame('EMAIL_SENT', $response->json('data.resendEmailVerification.status'));

        Notification::assertNothingSent();
    }

    /**
     * @test
     */
    public function it_does_not_resend_an_email_verification_notification_if_email_verification_is_not_used(): void
    {
        Notification::fake();

        $this->app['config']->set('auth.providers.users.model', UserHasApiTokens::class);

        UserHasApiTokens::factory()->create([
            'email'             => 'foo@bar.com',
            'email_verified_at' => null,
        ]);

        $response = $this->graphQL(/** @lang GraphQL */'
            mutation {
                resendEmailVerification(input: {
                    email: "foo@bar.com",
                }) {
                    status
                }
            }
        ')->assertJsonStructure([
            'data' => [
                'resendEmailVerification' => [
                    'status',
                ],
            ],
        ]);

        static::assertSame('EMAIL_SENT', $response->json('data.resendEmailVerification.status'));

        Notification::assertNothingSent();
    }

    /**
     * @test
     */
    public function it_does_not_resend_an_email_verification_notification_if_the_email_is_already_verified(): void
    {
        Notification::fake();

        $this->app['config']->set('auth.providers.users.model', UserMustVerifyEmail::class);

        UserMustVerifyEmail::factory()->create([
            'email'             => 'foo@bar.com',
            'email_verified_at' => Carbon::now(),
        ]);

        $response = $this->graphQL(/** @lang GraphQL */'
            mutation {
                resendEmailVerification(input: {
                    email: "foo@bar.com",
                }) {
                    status
                }
            }
        ')->assertJsonStructure([
            'data' => [
                'resendEmailVerification' => [
                    'status',
                ],
            ],
        ]);

        static::assertSame('EMAIL_SENT', $response->json('data.resendEmailVerification.status'));

        Notification::assertNothingSent();
    }

    /**
     * @test
     */
    public function it_returns_an_error_if_the_email_field_is_missing(): void
    {
        $this->graphQL(/** @lang GraphQL */'
            mutation {
                resendEmailVerification(input: {}) {
                    status
                }
            }
        ')->assertGraphQLErrorMessage('Field ResendEmailVerificationInput.email of required type String! was not provided.');
    }

    /**
     * @test
     */
    public function it_returns_an_error_if_the_email_field_is_not_a_string(): void
    {
        $this->graphQL(/** @lang GraphQL */'
            mutation {
                resendEmailVerification(input: {
                    email: 12345
                }) {
                    status
                }
            }
        ')->assertGraphQLErrorMessage('Field "resendEmailVerification" argument "input" requires type String!, found 12345.');
    }

    /**
     * @test
     */
    public function it_returns_an_error_if_the_email_field_is_not_an_email(): void
    {
        $this->graphQL(/** @lang GraphQL */'
            mutation {
                resendEmailVerification(input: {
                    email: "foobar"
                }) {
                    status
                }
            }
        ')
            ->assertGraphQLErrorMessage('Validation failed for the field [resendEmailVerification].')
            ->assertGraphQLValidationError(
                'input.email',
                'The input.email must be a valid email address.',
            );
    }

    /**
     * @test
     */
    public function it_returns_an_error_if_the_verification_url_field_is_missing(): void
    {
        $this->graphQL(/** @lang GraphQL */'
            mutation {
                resendEmailVerification(input: {
                    email: "foo@bar.com",
                    verification_url: {}
                }) {
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
                resendEmailVerification(input: {
                    email: "foo@bar.com",
                    verification_url: {
                        url: 12345
                    }
                }) {
                    status
                }
            }
        ')->assertGraphQLErrorMessage('Field "resendEmailVerification" argument "input" requires type String!, found 12345.');
    }

    /**
     * @test
     */
    public function it_returns_an_error_if_the_verification_url_field_is_not_a_url(): void
    {
        $this->graphQL(/** @lang GraphQL */'
            mutation {
                resendEmailVerification(input: {
                    email: "foo@bar.com",
                    verification_url: {
                        url: "google"
                    }
                }) {
                    status
                }
            }
        ')
            ->assertGraphQLErrorMessage('Validation failed for the field [resendEmailVerification].')
            ->assertGraphQLValidationError(
                'input.verification_url.url',
                'The input.verification url.url must be a valid URL.',
            );
    }
}
