<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Tests\Integration\GraphQL\Mutations;

use DanielDeWit\LighthouseSanctum\Tests\Integration\AbstractIntegrationTest;
use DanielDeWit\LighthouseSanctum\Tests\stubs\Users\UserMustVerifyEmail;
use Illuminate\Support\Facades\Notification;

class VerifyEmailTest extends AbstractIntegrationTest
{
    /**
     * @test
     */
    public function it_verifies_an_email(): void
    {
        Notification::fake();

        $this->app['config']->set('auth.providers.users.model', UserMustVerifyEmail::class);

        /** @var UserMustVerifyEmail $user */
        $user = UserMustVerifyEmail::factory()->create([
            'id'                => 123,
            'email_verified_at' => null,
        ]);

        $user->sendEmailVerificationNotification();

        $this->graphQL(/** @lang GraphQL */ '
            mutation {
                verifyEmail(input: {
                    id: 123,
                    hash: "' . sha1($user->getEmailForVerification()) . '"
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

        static::assertNotNull($user->getAttribute('email_verified_at'));
    }

    /**
     * @test
     */
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
        ')->assertGraphQLErrorMessage('The provided id and hash are incorrect.');
    }

    /**
     * @test
     */
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
        ')->assertGraphQLErrorMessage('The provided id and hash are incorrect.');
    }
}
