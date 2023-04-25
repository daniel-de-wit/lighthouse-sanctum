<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Tests\Unit\GraphQL\Mutations;

use DanielDeWit\LighthouseSanctum\Contracts\Services\EmailVerificationServiceInterface;
use DanielDeWit\LighthouseSanctum\GraphQL\Mutations\ResendEmailVerification;
use DanielDeWit\LighthouseSanctum\Tests\stubs\Users\UserHasApiTokens;
use DanielDeWit\LighthouseSanctum\Tests\stubs\Users\UserMustVerifyEmail;
use DanielDeWit\LighthouseSanctum\Tests\Traits\MocksUserProvider;
use DanielDeWit\LighthouseSanctum\Tests\Unit\AbstractUnitTestCase;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Foundation\Auth\User;
use Mockery;
use Mockery\MockInterface;

class ResendEmailVerificationTest extends AbstractUnitTestCase
{
    use MocksUserProvider;

    /**
     * @test
     */
    public function it_resends_an_email_verification_notification(): void
    {
        /** @var UserMustVerifyEmail|MockInterface $user */
        $user = Mockery::mock(UserMustVerifyEmail::class)
            ->shouldReceive('hasVerifiedEmail')
            ->andReturnFalse()
            ->getMock()
            ->shouldReceive('sendEmailVerificationNotification')
            ->getMock();

        $userProvider = $this->mockUserProvider($user);

        $mutation = new ResendEmailVerification(
            $this->mockAuthManager($userProvider),
            $this->mockConfig(),
            Mockery::mock(EmailVerificationServiceInterface::class),
        );

        $result = $mutation(null, [
            'email' => 'foo@bar.com',
        ]);

        static::assertSame('EMAIL_SENT', $result['status']);
    }

    /**
     * @test
     */
    public function it_sends_an_email_verification_notification_with_a_custom_url(): void
    {
        /** @var UserMustVerifyEmail|MockInterface $user */
        $user = Mockery::mock(UserMustVerifyEmail::class)
            ->shouldReceive('hasVerifiedEmail')
            ->andReturnFalse()
            ->getMock()
            ->shouldReceive('sendEmailVerificationNotification')
            ->getMock();

        $userProvider = $this->mockUserProvider($user);

        /** @var EmailVerificationServiceInterface|MockInterface $verificationService */
        $verificationService = Mockery::mock(EmailVerificationServiceInterface::class)
            ->shouldReceive('setVerificationUrl')
            ->with('custom-url')
            ->getMock();

        $mutation = new ResendEmailVerification(
            $this->mockAuthManager($userProvider),
            $this->mockConfig(),
            $verificationService,
        );

        $result = $mutation(null, [
            'email'            => 'foo@bar.com',
            'verification_url' => [
                'url' => 'custom-url',
            ],
        ]);

        static::assertSame('EMAIL_SENT', $result['status']);
    }

    /**
     * @test
     */
    public function it_does_not_resend_an_email_verification_notification_if_email_verification_is_not_used(): void
    {
        /** @var UserHasApiTokens|MockInterface $user */
        $user = Mockery::mock(UserHasApiTokens::class)
            ->shouldNotReceive('sendEmailVerificationNotification')
            ->getMock();

        $userProvider = $this->mockUserProvider($user);

        $mutation = new ResendEmailVerification(
            $this->mockAuthManager($userProvider),
            $this->mockConfig(),
            Mockery::mock(EmailVerificationServiceInterface::class),
        );

        $result = $mutation(null, [
            'email' => 'foo@bar.com',
        ]);

        static::assertSame('EMAIL_SENT', $result['status']);
    }

    /**
     * @test
     */
    public function it_does_not_resend_an_email_verification_notification_if_the_email_is_already_verified(): void
    {
        /** @var UserMustVerifyEmail|MockInterface $user */
        $user = Mockery::mock(UserMustVerifyEmail::class)
            ->shouldReceive('hasVerifiedEmail')
            ->andReturnTrue()
            ->getMock()
            ->shouldNotReceive('sendEmailVerificationNotification')
            ->getMock();

        $userProvider = $this->mockUserProvider($user);

        $mutation = new ResendEmailVerification(
            $this->mockAuthManager($userProvider),
            $this->mockConfig(),
            Mockery::mock(EmailVerificationServiceInterface::class),
        );

        $result = $mutation(null, [
            'email' => 'foo@bar.com',
        ]);

        static::assertSame('EMAIL_SENT', $result['status']);
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
                'email' => 'foo@bar.com',
            ])
            ->andReturn($user)
            ->getMock();

        return $userProvider;
    }
}
