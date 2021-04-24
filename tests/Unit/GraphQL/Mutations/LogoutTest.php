<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Tests\Unit\GraphQL\Mutations;

use DanielDeWit\LighthouseSanctum\Exceptions\HasApiTokensException;
use DanielDeWit\LighthouseSanctum\GraphQL\Mutations\Logout;
use DanielDeWit\LighthouseSanctum\Tests\stubs\Users\UserHasApiTokens;
use DanielDeWit\LighthouseSanctum\Tests\Unit\AbstractUnitTest;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Foundation\Auth\User;
use Laravel\Sanctum\PersonalAccessToken;
use Mockery;
use Mockery\MockInterface;
use RuntimeException;

class LogoutTest extends AbstractUnitTest
{
    /**
     * @test
     */
    public function it_logs_a_user_out(): void
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
        static::assertSame('TOKEN_REVOKED', $result['status']);
        static::assertSame('Translated string!', $result['message']);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_if_no_user_is_found_by_the_guard(): void
    {
        static::expectException(RuntimeException::class);
        static::expectExceptionMessage('Unable to detect current user.');

        $mutation = new Logout($this->mockAuthFactory(), Mockery::mock(Translator::class));

        $mutation(null, []);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_if_the_user_does_not_have_the_has_api_tokens_trait(): void
    {
        $user = Mockery::mock(User::class);

        static::expectException(HasApiTokensException::class);
        static::expectExceptionMessage('"' . get_class($user) . '" must implement "Laravel\Sanctum\Contracts\HasApiTokens".');

        $mutation = new Logout($this->mockAuthFactory($user), Mockery::mock(Translator::class));

        $mutation(null, []);
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
