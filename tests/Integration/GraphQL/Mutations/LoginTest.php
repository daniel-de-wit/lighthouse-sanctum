<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Tests\Integration\GraphQL\Mutations;

use DanielDeWit\LighthouseSanctum\Tests\Integration\AbstractIntegrationTest;
use DanielDeWit\LighthouseSanctum\Tests\stubs\Users\UserHasApiTokens;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;

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

        $this->makeRequest()->assertJsonStructure([
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
        $response = $this->makeRequest();

        $this->assertGraphQLErrorMessage($response, 'The provided credentials are incorrect.');
    }

    protected function makeRequest(): TestResponse
    {
        return $this->graphQL(/** @lang GraphQL */'
            mutation {
                login(input: {
                    email: "foo@bar.com",
                    password: "supersecret"
                }) {
                    token
                }
            }
        ');
    }
}
