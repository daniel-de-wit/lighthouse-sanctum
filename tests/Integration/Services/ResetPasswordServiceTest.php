<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Tests\Integration\Services;

use DanielDeWit\LighthouseSanctum\Services\ResetPasswordService;
use DanielDeWit\LighthouseSanctum\Tests\Integration\AbstractIntegrationTest;
use DanielDeWit\LighthouseSanctum\Tests\stubs\Users\UserMustVerifyEmail;
use Illuminate\Auth\Notifications\ResetPassword;

class ResetPasswordServiceTest extends AbstractIntegrationTest
{
    protected ResetPasswordService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ResetPasswordService();
    }

    /**
     * @test
     */
    public function it_sets_the_reset_password_url(): void
    {
        /** @var UserMustVerifyEmail $user */
        $user = UserMustVerifyEmail::factory()->create([
            'email' => 'user@example.com',
        ]);

        $token = 'token123';

        $this->service->setResetPasswordUrl('https://mysite.com/reset-password/__EMAIL__/__TOKEN__');

        $url = call_user_func(ResetPassword::$createUrlCallback, $user, $token);

        static::assertSame('https://mysite.com/reset-password/user@example.com/token123', $url);
    }
}
