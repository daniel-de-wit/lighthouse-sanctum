<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Tests\Integration\Services;

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

        $this->service = new EmailVerificationService();
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

        static::assertSame('https://mysite.com/verify-email/12345/' . sha1($user->getEmailForVerification()), $url);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_if_the_hash_is_incorrect(): void
    {
        static::expectException(AuthenticationException::class);
        static::expectExceptionMessage('The provided id and hash are incorrect.');

        $user = UserMustVerifyEmail::factory()->create();

        $this->service->verify($user, 'foobar');
    }
}
