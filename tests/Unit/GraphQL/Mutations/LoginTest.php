<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Tests\Unit\GraphQL\Mutations;

use DanielDeWit\LighthouseSanctum\Exceptions\HasApiTokensException;
use DanielDeWit\LighthouseSanctum\GraphQL\Mutations\Login;
use DanielDeWit\LighthouseSanctum\Tests\stubs\Users\UserHasApiTokens;
use DanielDeWit\LighthouseSanctum\Tests\stubs\Users\UserMustVerifyEmail;
use DanielDeWit\LighthouseSanctum\Tests\Traits\MocksUserProvider;
use DanielDeWit\LighthouseSanctum\Tests\Unit\AbstractUnitTest;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Foundation\Auth\User;
use Laravel\Sanctum\NewAccessToken;
use Mockery;
use Mockery\MockInterface;
use Nuwave\Lighthouse\Exceptions\AuthenticationException;
use RuntimeException;

class LoginTest extends AbstractUnitTest
{
    use MocksUserProvider;

    /**
     * @test
     */
    public function it_logs_a_user_in(): void
    {
        $token = Mockery::mock(NewAccessToken::class);
        $token->plainTextToken = '1234567890';

        /** @var UserHasApiTokens|MockInterface $user */
        $user = Mockery::mock(UserHasApiTokens::class)
            ->shouldReceive('createToken')
            ->with('default')
            ->andReturn($token)
            ->getMock();

        $userProvider = $this->mockUserProvider($user);
        $userProvider
            ->shouldReceive('validateCredentials')
            ->with($user, [
                'email'    => 'foo@bar.com',
                'password' => 'supersecret',
            ])
            ->andReturnTrue()
            ->getMock();

        /** @var Translator|MockInterface $translator */
        $translator = Mockery::mock(Translator::class);

        $mutation = new Login(
            $this->mockAuthManager($userProvider),
            $this->mockConfig(),
            $translator
        );

        $result = $mutation(null, [
            'email'    => 'foo@bar.com',
            'password' => 'supersecret',
        ]);

        static::assertIsArray($result);
        static::assertCount(1, $result);
        static::assertSame('1234567890', $result['token']);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_if_the_user_provider_is_not_found(): void
    {
        static::expectException(RuntimeException::class);
        static::expectExceptionMessage('User provider not found.');

        /** @var Translator|MockInterface $translator */
        $translator = Mockery::mock(Translator::class);

        $mutation = new Login(
            $this->mockAuthManager(null),
            $this->mockConfig(),
            $translator
        );

        $mutation(null, [
            'email'    => 'foo@bar.com',
            'password' => 'supersecret',
        ]);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_if_the_credentials_are_incorrect(): void
    {
        static::expectException(AuthenticationException::class);
        static::expectExceptionMessage('The provided credentials are incorrect.');

        $userProvider = $this->mockUserProvider(null);

        /** @var Translator|MockInterface $translator */
        $translator = Mockery::mock(Translator::class)
            ->shouldReceive('get')
            ->with('lighthouse-sanctum::exception.authentication_incorrect_credentials')
            ->andReturn('The provided credentials are incorrect.')->getMock();


        $mutation = new Login(
            $this->mockAuthManager($userProvider),
            $this->mockConfig(),
            $translator
        );

        $mutation(null, [
            'email'    => 'foo@bar.com',
            'password' => 'supersecret',
        ]);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_if_the_password_is_incorrect(): void
    {
        static::expectException(AuthenticationException::class);
        static::expectExceptionMessage('The provided credentials are incorrect.');

        /** @var UserHasApiTokens|MockInterface $user */
        $user = Mockery::mock(UserHasApiTokens::class);

        /** @var UserProvider|MockInterface $userProvider */
        $userProvider = Mockery::mock(UserProvider::class)
            ->shouldReceive('retrieveByCredentials')
            ->with([
                'email'    => 'foo@bar.com',
                'password' => 'wrong',
            ])
            ->andReturn($user)
            ->getMock();

        $userProvider
            ->shouldReceive('validateCredentials')
            ->with($user, [
                'email'    => 'foo@bar.com',
                'password' => 'wrong',
            ])
            ->andReturnFalse()
            ->getMock();

        /** @var Translator|MockInterface $translator */
        $translator = Mockery::mock(Translator::class)
            ->shouldReceive('get')
            ->with('lighthouse-sanctum::exception.authentication_incorrect_credentials')
            ->andReturn('The provided credentials are incorrect.')->getMock();


        $mutation = new Login(
            $this->mockAuthManager($userProvider),
            $this->mockConfig(),
            $translator
        );

        $mutation(null, [
            'email'    => 'foo@bar.com',
            'password' => 'wrong',
        ]);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_if_the_user_does_not_have_the_has_api_tokens_trait(): void
    {
        $user = Mockery::mock(User::class);

        static::expectException(HasApiTokensException::class);
        static::expectExceptionMessage(
            '"' . get_class($user) . '" must implement "Laravel\Sanctum\Contracts\HasApiTokens".'
        );

        $userProvider = $this->mockUserProvider($user);
        $userProvider
            ->shouldReceive('validateCredentials')
            ->with($user, [
                'email'    => 'foo@bar.com',
                'password' => 'supersecret',
            ])
            ->andReturnTrue()
            ->getMock();


        /** @var Translator|MockInterface $translator */
        $translator = Mockery::mock(Translator::class)
            ->shouldReceive('get')
            ->with(
                'lighthouse-sanctum::exception.has_api_tokens_exception',
                ['userClass' => get_class($user), 'apiTokenClass' => 'Laravel\Sanctum\Contracts\HasApiTokens']
            )
            ->andReturn('"' . get_class($user) . '" must implement "Laravel\Sanctum\Contracts\HasApiTokens".')
            ->getMock();

        $mutation = new Login(
            $this->mockAuthManager($userProvider),
            $this->mockConfig(),
            $translator
        );

        $mutation(null, [
            'email'    => 'foo@bar.com',
            'password' => 'supersecret',
        ]);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_if_the_users_email_is_not_verified(): void
    {
        static::expectException(AuthenticationException::class);
        static::expectExceptionMessage('Your email address is not verified.');

        /** @var UserMustVerifyEmail|MockInterface $user */
        $user = Mockery::mock(UserMustVerifyEmail::class)
            ->shouldReceive('hasVerifiedEmail')
            ->andReturnFalse()
            ->getMock();

        $userProvider = $this->mockUserProvider($user);
        $userProvider
            ->shouldReceive('validateCredentials')
            ->with($user, [
                'email'    => 'foo@bar.com',
                'password' => 'supersecret',
            ])
            ->andReturnTrue()
            ->getMock();

        /** @var Translator|MockInterface $translator */
        $translator = Mockery::mock(Translator::class)
            ->shouldReceive('get')
            ->with('lighthouse-sanctum::exception.authentication_email_not_verified')
            ->andReturn('Your email address is not verified.')
            ->getMock();

        $mutation = new Login(
            $this->mockAuthManager($userProvider),
            $this->mockConfig(),
            $translator
        );

        $mutation(null, [
            'email'    => 'foo@bar.com',
            'password' => 'supersecret',
        ]);
    }

    /**
     * @return UserProvider|MockInterface
     */
    protected function mockUserProvider(?User $user)
    {
        /** @var UserProvider|MockInterface $userProvider */
        $userProvider = Mockery::mock(UserProvider::class)
            ->shouldReceive('retrieveByCredentials')
            ->with([
                'email'    => 'foo@bar.com',
                'password' => 'supersecret',
            ])
            ->andReturn($user)
            ->getMock();

        return $userProvider;
    }
}
