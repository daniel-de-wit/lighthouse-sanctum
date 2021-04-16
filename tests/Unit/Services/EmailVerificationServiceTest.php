<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Tests\Unit\Services;

use DanielDeWit\LighthouseSanctum\Services\EmailVerificationService;
use DanielDeWit\LighthouseSanctum\Tests\Unit\AbstractUnitTest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Mockery;
use Mockery\MockInterface;
use Nuwave\Lighthouse\Exceptions\AuthenticationException;

class EmailVerificationServiceTest extends AbstractUnitTest
{
    protected EmailVerificationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new EmailVerificationService();
    }

    /**
     * @test
     */
    public function it_throws_an_exception_if_the_hash_is_incorrect(): void
    {
        static::expectException(AuthenticationException::class);
        static::expectExceptionMessage('The provided id and hash are incorrect.');

        $this->service->verify(
            $this->mockUser('user@example.com'),
            sha1('foo@bar.com'),
        );
    }

    /**
     * @test
     */
    public function it_does_nothing_if_the_hash_is_correct(): void
    {
        $this->service->verify(
            $this->mockUser('user@example.com'),
            sha1('user@example.com'),
        );
    }

    /**
     * @param string $email
     * @return MustVerifyEmail|MockInterface
     */
    protected function mockUser(string $email)
    {
        /** @var MustVerifyEmail|MockInterface $user */
        $user = Mockery::mock(MustVerifyEmail::class)
            ->shouldReceive('getEmailForVerification')
            ->andReturn($email)
            ->getMock();

        return $user;
    }
}
