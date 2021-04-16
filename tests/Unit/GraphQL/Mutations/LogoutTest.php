<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Tests\Unit\GraphQL\Mutations;

use DanielDeWit\LighthouseSanctum\Enums\LogoutStatus;
use DanielDeWit\LighthouseSanctum\GraphQL\Mutations\Logout;
use DanielDeWit\LighthouseSanctum\Tests\stubs\Users\UserHasApiTokens;
use DanielDeWit\LighthouseSanctum\Tests\Unit\AbstractUnitTest;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Translation\Translator;
use Laravel\Sanctum\PersonalAccessToken;
use Mockery;
use Mockery\MockInterface;

class LogoutTest extends AbstractUnitTest
{
    /**
     * @test
     */
    public function it_log_a_user_out(): void
    {
        /** @var PersonalAccessToken|MockInterface $token */
        $token = Mockery::mock(PersonalAccessToken::class)
            ->shouldReceive('delete')
            ->getMock();

        /** @var UserHasApiTokens|MockInterface $user */
        $user = Mockery::mock(UserHasApiTokens::class)
            ->shouldReceive('currentAccessToken')
            ->andReturn($token)
            ->getMock();

        /** @var Translator|MockInterface $translator */
        $translator = Mockery::mock(Translator::class)
            ->shouldReceive('get')
            ->with('Your session has been terminated')
            ->andReturn('Translated string!')
            ->getMock();

        $mutation = new Logout($this->mockAuthFactory($user), $translator);

        $result = $mutation(null, []);

        static::assertIsArray($result);
        static::assertCount(2, $result);
        static::assertTrue(LogoutStatus::TOKEN_REVOKED()->is($result['status']));
        static::assertSame('Translated string!', $result['message']);
    }

    /**
     * @param Authenticatable|MockInterface|null $user
     * @return AuthFactory|MockInterface
     */
    protected function mockAuthFactory($user = null)
    {
        /** @var Guard|MockInterface $guard */
        $guard = Mockery::mock(Guard::class)
            ->shouldReceive('user')
            ->andReturn($user)
            ->getMock();

        /** @var AuthFactory|MockInterface $authFactory */
        $authFactory = Mockery::mock(AuthFactory::class)
            ->shouldReceive('guard')
            ->with('sanctum')
            ->andReturn($guard)
            ->getMock();

        return $authFactory;
    }
}
