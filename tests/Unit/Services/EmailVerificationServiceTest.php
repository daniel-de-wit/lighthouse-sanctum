<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Tests\Unit\Services;

use Carbon\Carbon;
use DanielDeWit\LighthouseSanctum\Contracts\Services\SignatureServiceInterface;
use DanielDeWit\LighthouseSanctum\Services\EmailVerificationService;
use DanielDeWit\LighthouseSanctum\Tests\stubs\Users\UserMustVerifyEmail;
use DanielDeWit\LighthouseSanctum\Tests\Unit\AbstractUnitTestCase;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Mockery;
use Mockery\MockInterface;
use Nuwave\Lighthouse\Exceptions\AuthenticationException;

class EmailVerificationServiceTest extends AbstractUnitTestCase
{
    protected EmailVerificationService $service;

    /**
     * @var SignatureServiceInterface|MockInterface
     */
    protected $signatureService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->signatureService = Mockery::mock(SignatureServiceInterface::class);
        $this->service          = new EmailVerificationService($this->signatureService, 60);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_throws_an_exception_if_the_hash_is_incorrect(): void
    {
        static::expectException(AuthenticationException::class);
        static::expectExceptionMessage('The provided input is incorrect.');

        $this->service->verify(
            $this->mockUser('user@example.com'),
            sha1('foo@bar.com'),
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_does_nothing_if_the_hash_is_correct(): void
    {
        $this->service->verify(
            $this->mockUser('user@example.com'),
            sha1('user@example.com'),
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_throws_an_exception_if_the_expires_is_less_than_now(): void
    {
        static::expectException(AuthenticationException::class);
        static::expectExceptionMessage('The provided input is incorrect.');

        Carbon::setTestNow(Carbon::createFromTimestamp(1609477200));

        $this->service->verifySigned(
            $this->mockUser('user@example.com'),
            sha1('user@example.com'),
            1609476200,
            'signature',
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_throws_an_exception_if_the_signature_is_invalid(): void
    {
        static::expectException(AuthenticationException::class);
        static::expectExceptionMessage('The provided input is incorrect.');

        Carbon::setTestNow(Carbon::createFromTimestamp(1609477200));

        /** @var UserMustVerifyEmail|MockInterface $user */
        $user = Mockery::mock(UserMustVerifyEmail::class)
            ->shouldReceive('getEmailForVerification')
            ->andReturn('user@example.com')
            ->getMock()
            ->shouldReceive('getKey')
            ->andReturn(123)
            ->getMock();

        $this->signatureService
            ->shouldReceive('verify')
            ->with([
                'id'      => 123,
                'hash'    => sha1('user@example.com'),
                'expires' => 1609478200,
            ], 'signature')
            ->andThrow(InvalidSignatureException::class);

        $this->service->verifySigned(
            $user,
            sha1('user@example.com'),
            1609478200,
            'signature',
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_throws_an_exception(): void
    {
        static::expectException(AuthenticationException::class);
        static::expectExceptionMessage('The provided input is incorrect.');

        $this->service->throwAuthenticationException();
    }

    protected function mockUser(string $email): \Illuminate\Contracts\Auth\MustVerifyEmail|\Mockery\MockInterface
    {
        /** @var MustVerifyEmail|MockInterface $user */
        $user = Mockery::mock(MustVerifyEmail::class)
            ->shouldReceive('getEmailForVerification')
            ->once()
            ->andReturn($email)
            ->getMock();

        return $user;
    }
}
