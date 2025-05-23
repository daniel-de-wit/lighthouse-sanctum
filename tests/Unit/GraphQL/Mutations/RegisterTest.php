<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Tests\Unit\GraphQL\Mutations;

use DanielDeWit\LighthouseSanctum\Contracts\Services\EmailVerificationServiceInterface;
use DanielDeWit\LighthouseSanctum\Exceptions\HasApiTokensException;
use DanielDeWit\LighthouseSanctum\GraphQL\Mutations\Register;
use DanielDeWit\LighthouseSanctum\Tests\stubs\Users\UserHasApiTokens;
use DanielDeWit\LighthouseSanctum\Tests\stubs\Users\UserMustVerifyEmail;
use DanielDeWit\LighthouseSanctum\Tests\Traits\MocksUserProvider;
use DanielDeWit\LighthouseSanctum\Tests\Unit\AbstractUnitTestCase;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Foundation\Auth\User;
use Laravel\Sanctum\NewAccessToken;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;

class RegisterTest extends AbstractUnitTestCase
{
    use MocksUserProvider;

    #[Test]
    public function it_registers_a_user(): void
    {
        $token                 = Mockery::mock(NewAccessToken::class);
        $token->plainTextToken = '1234567890';

        /** @var UserHasApiTokens&MockInterface $user */
        $user = $this->mockUser(UserHasApiTokens::class)
            ->shouldReceive('createToken')
            ->with('default')
            ->andReturn($token)
            ->getMock();

        $userProvider = $this->mockUserProvider($user);

        $mutation = new Register(
            $this->mockAuthManager($userProvider),
            $this->mockConfig(),
            $this->mockHasher(),
            Mockery::mock(EmailVerificationServiceInterface::class),
        );

        $result = $mutation(null, [
            'name'                  => 'Foo Bar',
            'email'                 => 'foo@bar.com',
            'password'              => 'supersecret',
            'password_confirmation' => 'supersecret',
        ]);

        $this->assertSame('SUCCESS', $result['status']);
        $this->assertSame('1234567890', $result['token']);
    }

    #[Test]
    public function it_sends_an_email_verification_notification(): void
    {
        $token                 = Mockery::mock(NewAccessToken::class);
        $token->plainTextToken = '1234567890';

        /** @var UserMustVerifyEmail&MockInterface $user */
        $user = $this->mockUser(UserMustVerifyEmail::class)
            ->shouldReceive('sendEmailVerificationNotification')
            ->getMock()
            ->shouldReceive('createToken')
            ->with('default')
            ->andReturn($token)
            ->getMock();

        $userProvider = $this->mockUserProvider($user);

        $mutation = new Register(
            $this->mockAuthManager($userProvider),
            $this->mockConfig(),
            $this->mockHasher(),
            Mockery::mock(EmailVerificationServiceInterface::class),
        );

        $result = $mutation(null, [
            'name'                  => 'Foo Bar',
            'email'                 => 'foo@bar.com',
            'password'              => 'supersecret',
            'password_confirmation' => 'supersecret',
        ]);

        $this->assertSame('MUST_VERIFY_EMAIL', $result['status']);
        $this->assertNull($result['token']);
    }

    #[Test]
    public function it_sends_an_email_verification_notification_with_a_custom_url(): void
    {
        $token                 = Mockery::mock(NewAccessToken::class);
        $token->plainTextToken = '1234567890';

        /** @var UserMustVerifyEmail&MockInterface $user */
        $user = $this->mockUser(UserMustVerifyEmail::class)
            ->shouldReceive('sendEmailVerificationNotification')
            ->getMock()
            ->shouldReceive('createToken')
            ->with('default')
            ->andReturn($token)
            ->getMock();

        $userProvider = $this->mockUserProvider($user);

        /** @var EmailVerificationServiceInterface&MockInterface $verificationService */
        $verificationService = Mockery::mock(EmailVerificationServiceInterface::class)
            ->shouldReceive('setVerificationUrl')
            ->with('custom-url')
            ->getMock();

        $mutation = new Register(
            $this->mockAuthManager($userProvider),
            $this->mockConfig(),
            $this->mockHasher(),
            $verificationService,
        );

        $result = $mutation(null, [
            'name'                  => 'Foo Bar',
            'email'                 => 'foo@bar.com',
            'password'              => 'supersecret',
            'password_confirmation' => 'supersecret',
            'verification_url'      => [
                'url' => 'custom-url',
            ],
        ]);

        $this->assertSame('MUST_VERIFY_EMAIL', $result['status']);
        $this->assertNull($result['token']);
    }

    #[Test]
    public function it_throws_an_exception_if_the_user_provider_is_not_found(): void
    {
        static::expectException(RuntimeException::class);
        static::expectExceptionMessage('User provider not found.');

        $mutation = new Register(
            $this->mockAuthManager(null),
            $this->mockConfig(),
            $this->mockHasher(),
            Mockery::mock(EmailVerificationServiceInterface::class),
        );

        $mutation(null, [
            'name'                  => 'Foo Bar',
            'email'                 => 'foo@bar.com',
            'password'              => 'supersecret',
            'password_confirmation' => 'supersecret',
        ]);
    }

    #[Test]
    public function it_throws_an_exception_if_the_user_does_not_have_the_has_api_tokens_trait(): void
    {
        $user = $this->mockUser(User::class);

        static::expectException(HasApiTokensException::class);
        static::expectExceptionMessage('"'.$user::class.'" must implement "Laravel\Sanctum\Contracts\HasApiTokens".');

        $userProvider = $this->mockUserProvider($user);

        $mutation = new Register(
            $this->mockAuthManager($userProvider),
            $this->mockConfig(),
            $this->mockHasher(),
            Mockery::mock(EmailVerificationServiceInterface::class),
        );

        $mutation(null, [
            'name'                  => 'Foo Bar',
            'email'                 => 'foo@bar.com',
            'password'              => 'supersecret',
            'password_confirmation' => 'supersecret',
        ]);
    }

    protected function mockHasher(): Hasher&MockInterface
    {
        /** @var Hasher&MockInterface $hasher */
        $hasher = Mockery::mock(Hasher::class)
            ->shouldReceive('make')
            ->with('supersecret')
            ->andReturn('hashed-password')
            ->getMock();

        return $hasher;
    }

    protected function mockUserProvider(?User $user): UserProvider&MockInterface
    {
        /** @var UserProvider&MockInterface $userProvider */
        $userProvider = Mockery::mock(UserProvider::class)
            ->shouldReceive('createModel')
            ->andReturn($user)
            ->getMock();

        return $userProvider;
    }

    /**
     * @template T of User
     *
     * @param  class-string<T>  $class
     * @return T&MockInterface
     */
    protected function mockUser(string $class)
    {
        /** @var T&MockInterface $user */
        $user = Mockery::mock($class)
            ->shouldReceive('fill')
            ->with([
                'name'     => 'Foo Bar',
                'email'    => 'foo@bar.com',
                'password' => 'hashed-password',
            ])
            ->andReturnSelf()
            ->getMock()
            ->shouldReceive('save')
            ->andReturnTrue()
            ->getMock();

        return $user;
    }
}
