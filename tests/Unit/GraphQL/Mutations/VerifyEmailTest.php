<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Tests\Unit\GraphQL\Mutations;

use DanielDeWit\LighthouseSanctum\Contracts\Services\EmailVerificationServiceInterface;
use DanielDeWit\LighthouseSanctum\GraphQL\Mutations\VerifyEmail;
use DanielDeWit\LighthouseSanctum\Tests\stubs\Users\UserMustVerifyEmail;
use DanielDeWit\LighthouseSanctum\Tests\Traits\MocksUserProvider;
use DanielDeWit\LighthouseSanctum\Tests\Unit\AbstractUnitTest;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Foundation\Auth\User;
use Illuminate\Validation\Validator;
use Mockery;
use Mockery\MockInterface;
use Nuwave\Lighthouse\Exceptions\ValidationException;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
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

        /** @var Config|MockInterface $config */
        $config = $this->mockConfig()
            ->shouldReceive('get')
            ->with('lighthouse-sanctum.use_signed_email_verification_url')
            ->andReturnFalse()
            ->getMock();

        $mutation = new VerifyEmail(
            $this->mockAuthManager($userProvider),
            $config,
            Mockery::mock(ValidationFactory::class),
            $verificationService,
        );

        $result = $mutation(null, [
            'id'   => 123,
            'hash' => '1234567890',
        ], Mockery::mock(GraphQLContext::class), Mockery::mock(ResolveInfo::class));

        static::assertIsArray($result);
        static::assertCount(1, $result);
        static::assertSame('VERIFIED', $result['status']);
    }

    /**
     * @test
     */
    public function it_verifies_an_email_with_a_signature(): void
    {
        /** @var UserMustVerifyEmail|MockInterface $user */
        $user = Mockery::mock(UserMustVerifyEmail::class)
            ->shouldReceive('markEmailAsVerified')
            ->getMock();

        /** @var EmailVerificationServiceInterface|MockInterface $verificationService */
        $verificationService = Mockery::mock(EmailVerificationServiceInterface::class)
            ->shouldReceive('verifySigned')
            ->with($user, '1234567890', 60, 'signature')
            ->getMock();

        $userProvider = $this->mockUserProvider($user);

        /** @var Config|MockInterface $config */
        $config = $this->mockConfig()
            ->shouldReceive('get')
            ->with('lighthouse-sanctum.use_signed_email_verification_url')
            ->andReturnTrue()
            ->getMock();

        $mutation = new VerifyEmail(
            $this->mockAuthManager($userProvider),
            $config,
            $this->mockValidator(),
            $verificationService,
        );

        $resolveInfo = Mockery::mock(ResolveInfo::class);
        $resolveInfo->path = ['foo', 'bar'];

        $result = $mutation(null, [
            'id'        => 123,
            'hash'      => '1234567890',
            'expires'   => 60,
            'signature' => 'signature',
        ], Mockery::mock(GraphQLContext::class), $resolveInfo);

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
            Mockery::mock(ValidationFactory::class),
            Mockery::mock(EmailVerificationServiceInterface::class),
        );

        $mutation(null, [
            'id'   => 123,
            'hash' => '1234567890',
        ], Mockery::mock(GraphQLContext::class), Mockery::mock(ResolveInfo::class));
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
            Mockery::mock(ValidationFactory::class),
            Mockery::mock(EmailVerificationServiceInterface::class),
        );

        $mutation(null, [
            'id'   => 123,
            'hash' => '1234567890',
        ], Mockery::mock(GraphQLContext::class), Mockery::mock(ResolveInfo::class));
    }

    /**
     * @test
     */
    public function it_throws_an_exception_if_required_signed_arguments_are_missing(): void
    {
        static::expectException(ValidationException::class);
        static::expectExceptionMessage('Validation failed for the field [foo.bar].');

        $userProvider = $this->mockUserProvider(Mockery::mock(UserMustVerifyEmail::class));

        /** @var Config|MockInterface $config */
        $config = $this->mockConfig()
            ->shouldReceive('get')
            ->with('lighthouse-sanctum.use_signed_email_verification_url')
            ->andReturnTrue()
            ->getMock();

        $mutation = new VerifyEmail(
            $this->mockAuthManager($userProvider),
            $config,
            $this->mockValidator(false),
            Mockery::mock(EmailVerificationServiceInterface::class),
        );

        $resolveInfo = Mockery::mock(ResolveInfo::class);
        $resolveInfo->path = ['foo', 'bar'];

        $mutation(null, [
            'id'        => 123,
            'hash'      => '1234567890',
            'expires'   => 60,
            'signature' => 'signature',
        ], Mockery::mock(GraphQLContext::class), $resolveInfo);
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

    /**
     * @param bool $isValid
     * @return ValidationFactory|MockInterface
     */
    protected function mockValidator(bool $isValid = true)
    {
        /** @var Validator|MockInterface $validator */
        $validator = Mockery::mock(Validator::class)
            ->shouldReceive('fails')
            ->andReturn(! $isValid)
            ->getMock();

        /** @var ValidationFactory|MockInterface $factory */
        $factory = Mockery::mock(ValidationFactory::class)
            ->shouldReceive('make')
            ->with([
                'id'        => 123,
                'hash'      => '1234567890',
                'expires'   => 60,
                'signature' => 'signature',
            ], [
                'expires'   => ['required'],
                'signature' => ['required'],
            ])
            ->andReturn($validator)
            ->getMock();

        return $factory;
    }
}
