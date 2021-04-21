<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Tests\Integration\GraphQL\Mutations;

use DanielDeWit\LighthouseSanctum\Tests\Integration\AbstractIntegrationTest;
use DanielDeWit\LighthouseSanctum\Tests\stubs\Users\UserHasApiTokens;
use Laravel\Sanctum\Sanctum;

class LogoutTest extends AbstractIntegrationTest
{
    /**
     * @test
     */
    public function it_logs_a_user_out(): void
    {
        Sanctum::actingAs(UserHasApiTokens::factory()->create());

        $this->graphQL(/** @lang GraphQL */'
            mutation {
                logout {
                    status
                    message
                }
            }
        ')->assertJson([
            'data' => [
                'logout' => [
                    'status'  => 'TOKEN_REVOKED',
                    'message' => 'Your session has been terminated',
                ],
            ],
        ]);
    }

    /**
     * @test
     */
    public function it_returns_an_error_if_the_user_is_unauthenticated(): void
    {
        $response = $this->graphQL(/** @lang GraphQL */'
            mutation {
                logout {
                    status
                    message
                }
            }
        ');

        $this->assertGraphQLErrorMessage($response, 'Unauthenticated.');
    }
}
