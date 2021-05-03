<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Tests\Integration\Services;

use Carbon\Carbon;
use DanielDeWit\LighthouseSanctum\Contracts\Services\SignatureServiceInterface;
use DanielDeWit\LighthouseSanctum\Services\EmailVerificationService;
use DanielDeWit\LighthouseSanctum\Tests\Integration\AbstractIntegrationTest;
use DanielDeWit\LighthouseSanctum\Tests\stubs\Users\UserMustVerifyEmail;
use Illuminate\Auth\Notifications\VerifyEmail;
use Nuwave\Lighthouse\Exceptions\AuthenticationException;

class EmailVerificationServiceTest extends AbstractIntegrationTest
{
    protected EmailVerificationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new EmailVerificationService(
            $this->app->make(SignatureServiceInterface::class),
            60,
        );
    }

    /**
     * @test
     */
    public function it_sets_the_verification_url(): void
    {
        /** @var UserMustVerifyEmail $user */
        $user = UserMustVerifyEmail::factory()->create([
            'id'    => 12345,
            'email' => 'user@example.com',
        ]);

        $this->service->setVerificationUrl('https://mysite.com/verify-email/__ID__/__HASH__');

        $url = call_user_func(VerifyEmail::$createUrlCallback, $user);

        static::assertSame('https://mysite.com/verify-email/12345/' . sha1('user@example.com'), $url);
    }

    /**
     * @test
     */
    public function it_sets_the_signed_verification_url(): void
    {
        Carbon::setTestNow(Carbon::createFromTimestamp(1609477200));

        /** @var UserMustVerifyEmail $user */
        $user = UserMustVerifyEmail::factory()->create([
            'id'    => 12345,
            'email' => 'user@example.com',
        ]);

        $this->service->setVerificationUrl('https://mysite.com/verify-email/__ID__/__HASH__/__EXPIRES__/__SIGNATURE__');

        $url = call_user_func(VerifyEmail::$createUrlCallback, $user);

        $signature = hash_hmac('sha256', serialize([
            'id'      => 12345,
            'hash'    => sha1('user@example.com'),
            'expires' => 1609480800,
        ]), $this->app['config']->get('app.key'));

        static::assertSame('https://mysite.com/verify-email/12345/' . sha1('user@example.com') . '/1609480800/' . $signature, $url);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_if_the_hash_is_incorrect(): void
    {
        static::expectException(AuthenticationException::class);
        static::expectExceptionMessage('The provided input is incorrect.');

        $user = UserMustVerifyEmail::factory()->create();

        $this->service->verify($user, 'foobar');
    }

    /**
     * @test
     */
    public function it_throws_an_exception_if_the_expires_is_less_than_now(): void
    {
        static::expectException(AuthenticationException::class);
        static::expectExceptionMessage('The provided input is incorrect.');

        Carbon::setTestNow(Carbon::createFromTimestamp(1609477200));

        $user = UserMustVerifyEmail::factory()->create();

        $this->service->verifySigned($user, 'foobar', 1609476200, 'signature');
    }

    /**
     * @test
     */
    public function it_throws_an_exception_if_the_signature_is_invalid(): void
    {
        static::expectException(AuthenticationException::class);
        static::expectExceptionMessage('The provided input is incorrect.');

        Carbon::setTestNow(Carbon::createFromTimestamp(1609477200));

        /** @var UserMustVerifyEmail $user */
        $user = UserMustVerifyEmail::factory()->create([
            'id'    => 12345,
            'email' => 'user@example.com',
        ]);

        $this->service->verifySigned($user, sha1('user@example.com'), 1609480800, 'invalid-signature');
    }
}
