<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Tests\Integration\GraphQL\Mutations;

use DanielDeWit\LighthouseSanctum\Tests\Integration\AbstractIntegrationTestCase;
use DanielDeWit\LighthouseSanctum\Tests\stubs\Users\UserHasApiTokens;
use Laravel\Sanctum\Sanctum;
use Orchestra\Testbench\Attributes\WithMigration;
use PHPUnit\Framework\Attributes\Test;

class LogoutTest extends AbstractIntegrationTestCase
{
    #[Test]
    #[WithMigration]
    public function it_logs_a_user_out(): void
    {
        Sanctum::actingAs(UserHasApiTokens::factory()->create());

        $this->graphQL(/** @lang GraphQL */ '
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

    #[Test]
    #[WithMigration]
    public function it_returns_an_error_if_the_user_is_unauthenticated(): void
    {
        $this->graphQL(/** @lang GraphQL */ '
            mutation {
                logout {
                    status
                    message
                }
            }
        ')
            ->assertGraphQLErrorMessage('Unauthenticated.');
    }
}
