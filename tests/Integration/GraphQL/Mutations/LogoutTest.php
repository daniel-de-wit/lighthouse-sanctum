<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Tests\Integration\GraphQL\Mutations;

use DanielDeWit\LighthouseSanctum\GraphQL\Mutations\Logout;
use DanielDeWit\LighthouseSanctum\Tests\Integration\AbstractIntegrationTest;
use DanielDeWit\LighthouseSanctum\Tests\stubs\Users\UserHasApiTokens;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Translation\Translator;
use Laravel\Sanctum\Sanctum;

class LogoutTest extends AbstractIntegrationTest
{
    protected Logout $mutation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mutation = new Logout(
            $this->app->make(AuthFactory::class),
            $this->app->make(Translator::class),
        );
    }

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
}
