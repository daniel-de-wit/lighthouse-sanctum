<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Tests\Unit\GraphQL\Mutations;

use DanielDeWit\LighthouseSanctum\Contracts\Services\EmailVerificationServiceInterface;
use DanielDeWit\LighthouseSanctum\GraphQL\Mutations\VerifyEmail;
use DanielDeWit\LighthouseSanctum\Tests\stubs\Users\UserMustVerifyEmail;
use DanielDeWit\LighthouseSanctum\Tests\Traits\MocksUserProvider;
use DanielDeWit\LighthouseSanctum\Tests\Unit\AbstractUnitTest;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Foundation\Auth\User;
use Mockery;
use Mockery\MockInterface;
use RuntimeException;

class VerifyEmailTest extends AbstractUnitTest
{
    use MocksUserProvider;

    /**
     * @test
     */
    public function it_verifies_an_email(): void
    {
        /** @var UserMustVerifyEmail|MockInterface $user */
        $user = Mockery::mock(UserMustVerifyEmail::class)
            ->shouldReceive('markEmailAsVerified')
            ->getMock();

        /** @var EmailVerificationServiceInterface|MockInterface $verificationService */
        $verificationService = Mockery::mock(EmailVerificationServiceInterface::class)
            ->shouldReceive('verify')
            ->with($user, '1234567890')
            ->getMock();

        $userProvider = $this->mockUserProvider($user);

        $mutation = new VerifyEmail(
            $this->mockAuthManager($userProvider),
            $this->mockConfig(),
            $verificationService,
        );

        $result = $mutation(null, [
            'id'   => 123,
            'hash' => '1234567890',
        ]);

        static::assertIsArray($result);
        static::assertCount(1, $result);
        static::assertSame('VERIFIED', $result['status']);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_if_the_user_provider_is_not_found(): void
    {
        static::expectException(RuntimeException::class);
        static::expectExceptionMessage('User provider not found.');

        $mutation = new VerifyEmail(
            $this->mockAuthManager(null),
            $this->mockConfig(),
            Mockery::mock(EmailVerificationServiceInterface::class),
        );

        $mutation(null, [
            'id'   => 123,
            'hash' => '1234567890',
        ]);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_if_the_user_is_not_found(): void
    {
        static::expectException(AuthenticationException::class);
        static::expectExceptionMessage('The provided id and hash are incorrect.');

        $userProvider = $this->mockUserProvider(null);

        $mutation = new VerifyEmail(
            $this->mockAuthManager($userProvider),
            $this->mockConfig(),
            Mockery::mock(EmailVerificationServiceInterface::class),
        );

        $mutation(null, [
            'id'   => 123,
            'hash' => '1234567890',
        ]);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_if_the_user_does_not_implement_the_must_verify_email_interface(): void
    {
        $user = Mockery::mock(User::class);

        static::expectException(RuntimeException::class);
        static::expectExceptionMessage('User must implement "Illuminate\Contracts\Auth\MustVerifyEmail".');

        $userProvider = $this->mockUserProvider($user);

        $mutation = new VerifyEmail(
            $this->mockAuthManager($userProvider),
            $this->mockConfig(),
            Mockery::mock(EmailVerificationServiceInterface::class),
        );

        $mutation(null, [
            'id'   => 123,
            'hash' => '1234567890',
        ]);
    }

    /**
     * @return UserProvider|MockInterface
     */
    protected function mockUserProvider(?User $user)
    {
        /** @var UserProvider|MockInterface $userProvider */
        $userProvider = Mockery::mock(UserProvider::class)
            ->shouldReceive('retrieveById')
            ->with(123)
            ->andReturn($user)
            ->getMock();

        return $userProvider;
    }
}
