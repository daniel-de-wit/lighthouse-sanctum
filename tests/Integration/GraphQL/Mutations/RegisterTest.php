<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Tests\Integration\GraphQL\Mutations;

use DanielDeWit\LighthouseSanctum\Enums\RegisterStatus;
use DanielDeWit\LighthouseSanctum\Tests\Integration\AbstractIntegrationTest;
use DanielDeWit\LighthouseSanctum\Tests\stubs\Users\UserHasApiTokens;
use DanielDeWit\LighthouseSanctum\Tests\stubs\Users\UserMustVerifyEmail;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;

class RegisterTest extends AbstractIntegrationTest
{
    /**
     * @test
     */
    public function it_registers_a_user(): void
    {
        $response = $this->makeRequest()->assertJsonStructure([
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

        $response = $this->makeRequest()->assertJsonStructure([
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
    public function unique_email(): void
    {
        UserHasApiTokens::factory()->create([
            'email' => 'foo@bar.com',
        ]);

        $this->makeRequest()
            ->assertGraphQLErrorMessage('Validation failed for the field [register].')
            ->assertGraphQLValidationError('input', 'The input must be unique.');
    }

    protected function makeRequest(): TestResponse
    {
        return $this->graphQL(/** @lang GraphQL */'
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
        ');
    }
}
