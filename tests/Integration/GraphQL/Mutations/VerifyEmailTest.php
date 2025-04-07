<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Tests\Integration\GraphQL\Mutations;

use Carbon\Carbon;
use DanielDeWit\LighthouseSanctum\Tests\Integration\AbstractIntegrationTestCase;
use DanielDeWit\LighthouseSanctum\Tests\stubs\Users\UserMustVerifyEmail;
use Orchestra\Testbench\Attributes\WithMigration;
use PHPUnit\Framework\Attributes\Test;

class VerifyEmailTest extends AbstractIntegrationTestCase
{
    #[Test]
    #[WithMigration]
    public function it_verifies_an_email(): void
    {
        $this->app['config']->set('auth.providers.users.model', UserMustVerifyEmail::class);

        /** @var UserMustVerifyEmail $user */
        $user = UserMustVerifyEmail::factory()->create([
            'id'                => 123,
            'email'             => 'john.doe@gmail.com',
            'email_verified_at' => null,
        ]);

        $this->graphQL(/** @lang GraphQL */ '
            mutation {
                verifyEmail(input: {
                    id: 123,
                    hash: "'.sha1('john.doe@gmail.com').'"
                }) {
                    status
                }
            }
        ')->assertJson([
            'data' => [
                'verifyEmail' => [
                    'status' => 'VERIFIED',
                ],
            ],
        ]);

        $user->refresh();

        $this->assertNotNull($user->getAttribute('email_verified_at'));
    }

    #[Test]
    #[WithMigration]
    public function it_verifies_an_email_with_a_signature(): void
    {
        Carbon::setTestNow(Carbon::createFromTimestamp(1609477200));

        $this->app['config']->set('auth.providers.users.model', UserMustVerifyEmail::class);
        $this->app['config']->set('lighthouse-sanctum.use_signed_email_verification_url', true);

        /** @var UserMustVerifyEmail $user */
        $user = UserMustVerifyEmail::factory()->create([
            'id'                => 123,
            'email'             => 'john.doe@gmail.com',
            'email_verified_at' => null,
        ]);

        $signature = hash_hmac('sha256', serialize([
            'id'      => 123,
            'hash'    => sha1('john.doe@gmail.com'),
            'expires' => 1609480800,
        ]), $this->getAppKey());

        $this->graphQL(/** @lang GraphQL */ '
            mutation {
                verifyEmail(input: {
                    id: 123,
                    hash: "'.sha1('john.doe@gmail.com').'",
                    expires: 1609480800,
                    signature: "'.$signature.'"
                }) {
                    status
                }
            }
        ')->assertJson([
            'data' => [
                'verifyEmail' => [
                    'status' => 'VERIFIED',
                ],
            ],
        ]);

        $user->refresh();

        $this->assertNotNull($user->getAttribute('email_verified_at'));
    }

    #[Test]
    #[WithMigration]
    public function it_returns_an_error_if_the_user_is_not_found(): void
    {
        $this->graphQL(/** @lang GraphQL */ '
            mutation {
                verifyEmail(input: {
                    id: 123,
                    hash: "foobar"
                }) {
                    status
                }
            }
        ')->assertGraphQLErrorMessage('The provided input is incorrect.');
    }

    #[Test]
    #[WithMigration]
    public function it_returns_an_error_if_the_hash_is_incorrect(): void
    {
        $this->app['config']->set('auth.providers.users.model', UserMustVerifyEmail::class);

        UserMustVerifyEmail::factory()->create([
            'id'                => 123,
            'email_verified_at' => null,
        ]);

        $this->graphQL(/** @lang GraphQL */ '
            mutation {
                verifyEmail(input: {
                    id: 123,
                    hash: "foobar"
                }) {
                    status
                }
            }
        ')->assertGraphQLErrorMessage('The provided input is incorrect.');
    }

    #[Test]
    #[WithMigration]
    public function it_returns_an_error_if_the_expires_is_incorrect(): void
    {
        Carbon::setTestNow(Carbon::createFromTimestamp(1609477200));

        $this->app['config']->set('auth.providers.users.model', UserMustVerifyEmail::class);
        $this->app['config']->set('lighthouse-sanctum.use_signed_email_verification_url', true);

        UserMustVerifyEmail::factory()->create([
            'id'                => 123,
            'email'             => 'john.doe@gmail.com',
            'email_verified_at' => null,
        ]);

        $signature = hash_hmac('sha256', serialize([
            'id'      => 123,
            'hash'    => sha1('john.doe@gmail.com'),
            'expires' => 1609480800,
        ]), $this->getAppKey());

        $this->graphQL(/** @lang GraphQL */ '
            mutation {
                verifyEmail(input: {
                    id: 123,
                    hash: "'.sha1('john.doe@gmail.com').'"
                    expires: 456,
                    signature: "'.$signature.'"
                }) {
                    status
                }
            }
        ')->assertGraphQLErrorMessage('The provided input is incorrect.');
    }

    #[Test]
    #[WithMigration]
    public function it_returns_an_error_if_the_signature_has_expired(): void
    {
        Carbon::setTestNow(Carbon::createFromTimestamp(1609477200));

        $this->app['config']->set('auth.providers.users.model', UserMustVerifyEmail::class);
        $this->app['config']->set('lighthouse-sanctum.use_signed_email_verification_url', true);

        UserMustVerifyEmail::factory()->create([
            'id'                => 123,
            'email'             => 'john.doe@gmail.com',
            'email_verified_at' => null,
        ]);

        $signature = hash_hmac('sha256', serialize([
            'id'      => 123,
            'hash'    => sha1('john.doe@gmail.com'),
            'expires' => 1609476200,
        ]), $this->getAppKey());

        $this->graphQL(/** @lang GraphQL */ '
            mutation {
                verifyEmail(input: {
                    id: 123,
                    hash: "'.sha1('john.doe@gmail.com').'"
                    expires: 1609476200,
                    signature: "'.$signature.'"
                }) {
                    status
                }
            }
        ')->assertGraphQLErrorMessage('The provided input is incorrect.');
    }

    #[Test]
    #[WithMigration]
    public function it_returns_an_error_if_the_signature_is_incorrect(): void
    {
        Carbon::setTestNow(Carbon::createFromTimestamp(1609477200));

        $this->app['config']->set('auth.providers.users.model', UserMustVerifyEmail::class);
        $this->app['config']->set('lighthouse-sanctum.use_signed_email_verification_url', true);

        UserMustVerifyEmail::factory()->create([
            'id'                => 123,
            'email'             => 'john.doe@gmail.com',
            'email_verified_at' => null,
        ]);

        $this->graphQL(/** @lang GraphQL */ '
            mutation {
                verifyEmail(input: {
                    id: 123,
                    hash: "'.sha1('john.doe@gmail.com').'"
                    expires: 1609480800,
                    signature: "1234567890"
                }) {
                    status
                }
            }
        ')->assertGraphQLErrorMessage('The provided input is incorrect.');
    }

    #[Test]
    #[WithMigration]
    public function it_returns_an_error_if_the_id_field_is_missing(): void
    {
        $this->graphQL(/** @lang GraphQL */ '
            mutation {
                verifyEmail(input: {
                    hash: "foobar"
                }) {
                    status
                }
            }
        ')->assertGraphQLErrorMessage('Field VerifyEmailInput.id of required type ID! was not provided.');
    }

    #[Test]
    #[WithMigration]
    public function it_returns_an_error_if_the_id_field_is_not_an_id(): void
    {
        $this->graphQL(/** @lang GraphQL */ '
            mutation {
                verifyEmail(input: {
                    id: true,
                    hash: "foobar"
                }) {
                    status
                }
            }
        ')->assertGraphQLErrorMessage('ID cannot represent a non-string and non-integer value: true');
    }

    #[Test]
    #[WithMigration]
    public function it_returns_an_error_if_the_hash_field_is_missing(): void
    {
        $this->graphQL(/** @lang GraphQL */ '
            mutation {
                verifyEmail(input: {
                    id: 123,
                }) {
                    status
                }
            }
        ')->assertGraphQLErrorMessage('Field VerifyEmailInput.hash of required type String! was not provided.');
    }

    #[Test]
    #[WithMigration]
    public function it_returns_an_error_if_the_hash_field_is_not_a_string(): void
    {
        $this->graphQL(/** @lang GraphQL */ '
            mutation {
                verifyEmail(input: {
                    id: 123,
                    hash: 12345
                }) {
                    status
                }
            }
        ')->assertGraphQLErrorMessage('String cannot represent a non string value: 12345');
    }

    #[Test]
    #[WithMigration]
    public function it_returns_an_error_if_the_expires_field_is_missing_when_using_signed_verification(): void
    {
        $this->app['config']->set('auth.providers.users.model', UserMustVerifyEmail::class);
        $this->app['config']->set('lighthouse-sanctum.use_signed_email_verification_url', true);

        UserMustVerifyEmail::factory()->create([
            'id'                => 123,
            'email'             => 'john.doe@gmail.com',
            'email_verified_at' => null,
        ]);

        $this->graphQL(/** @lang GraphQL */ '
            mutation {
                verifyEmail(input: {
                    id: 123,
                    hash: "foobar",
                    signature: "1234567890"
                }) {
                    status
                }
            }
        ')
            ->assertGraphQLErrorMessage('Validation failed for the field [verifyEmail].')
            ->assertGraphQLValidationError(
                'expires',
                'The expires field is required.',
            );
    }

    #[Test]
    #[WithMigration]
    public function it_returns_an_error_if_the_expires_field_is_not_an_int(): void
    {
        $this->app['config']->set('auth.providers.users.model', UserMustVerifyEmail::class);
        $this->app['config']->set('lighthouse-sanctum.use_signed_email_verification_url', true);

        UserMustVerifyEmail::factory()->create([
            'id'                => 123,
            'email'             => 'john.doe@gmail.com',
            'email_verified_at' => null,
        ]);

        $this->graphQL(/** @lang GraphQL */ '
            mutation {
                verifyEmail(input: {
                    id: 123,
                    hash: "foobar",
                    expires: true,
                    signature: "1234567890"
                }) {
                    status
                }
            }
        ')->assertGraphQLErrorMessage('Int cannot represent non-integer value: true');
    }

    #[Test]
    #[WithMigration]
    public function it_returns_an_error_if_the_signature_field_is_missing_when_using_signed_verification(): void
    {
        $this->app['config']->set('auth.providers.users.model', UserMustVerifyEmail::class);
        $this->app['config']->set('lighthouse-sanctum.use_signed_email_verification_url', true);

        UserMustVerifyEmail::factory()->create([
            'id'                => 123,
            'email'             => 'john.doe@gmail.com',
            'email_verified_at' => null,
        ]);

        $this->graphQL(/** @lang GraphQL */ '
            mutation {
                verifyEmail(input: {
                    id: 123,
                    hash: "foobar",
                    expires: 1609480800
                }) {
                    status
                }
            }
        ')
            ->assertGraphQLErrorMessage('Validation failed for the field [verifyEmail].')
            ->assertGraphQLValidationError(
                'signature',
                'The signature field is required.',
            );
    }

    #[Test]
    #[WithMigration]
    public function it_returns_an_error_if_the_signature_field_is_not_a_string(): void
    {
        $this->app['config']->set('auth.providers.users.model', UserMustVerifyEmail::class);
        $this->app['config']->set('lighthouse-sanctum.use_signed_email_verification_url', true);

        UserMustVerifyEmail::factory()->create([
            'id'                => 123,
            'email'             => 'john.doe@gmail.com',
            'email_verified_at' => null,
        ]);

        $this->graphQL(/** @lang GraphQL */ '
            mutation {
                verifyEmail(input: {
                    id: 123,
                    hash: "foobar",
                    expires: 1609480800,
                    signature: 12345
                }) {
                    status
                }
            }
        ')->assertGraphQLErrorMessage('String cannot represent a non string value: 12345');
    }
}
