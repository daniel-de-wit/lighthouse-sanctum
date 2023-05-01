<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Tests\Unit\GraphQL\Mutations;

use DanielDeWit\LighthouseSanctum\Contracts\Services\ResetPasswordServiceInterface;
use DanielDeWit\LighthouseSanctum\GraphQL\Mutations\ForgotPassword;
use DanielDeWit\LighthouseSanctum\Tests\Unit\AbstractUnitTestCase;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Contracts\Translation\Translator;
use Mockery;
use Mockery\MockInterface;

class ForgotPasswordTest extends AbstractUnitTestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function it_sends_a_reset_password_notification(): void
    {
        /** @var PasswordBroker&MockInterface $passwordBroker */
        $passwordBroker = Mockery::mock(PasswordBroker::class)
            ->shouldReceive('sendResetLink')
            ->with([
                'email' => 'foo@bar.com',
            ])
            ->getMock();

        /** @var Translator&MockInterface $translator */
        $translator = Mockery::mock(Translator::class)
            ->shouldReceive('get')
            ->with('An email has been sent')
            ->andReturn('translation')
            ->getMock();

        $mutation = new ForgotPassword(
            $passwordBroker,
            Mockery::mock(ResetPasswordServiceInterface::class),
            $translator,
        );

        $result = $mutation(null, [
            'email' => 'foo@bar.com',
        ]);

        static::assertIsArray($result);
        static::assertCount(2, $result);
        static::assertSame('EMAIL_SENT', $result['status']);
        static::assertSame('translation', $result['message']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_sends_a_reset_password_notification_with_a_custom_url(): void
    {
        /** @var PasswordBroker&MockInterface $passwordBroker */
        $passwordBroker = Mockery::mock(PasswordBroker::class)
            ->shouldReceive('sendResetLink')
            ->with([
                'email' => 'foo@bar.com',
            ])
            ->getMock();

        /** @var ResetPasswordServiceInterface&MockInterface $resetService */
        $resetService = Mockery::mock(ResetPasswordServiceInterface::class)
            ->shouldReceive('setResetPasswordUrl')
            ->with('custom-url')
            ->getMock();

        /** @var Translator&MockInterface $translator */
        $translator = Mockery::mock(Translator::class)
            ->shouldReceive('get')
            ->with('An email has been sent')
            ->andReturn('translation')
            ->getMock();

        $mutation = new ForgotPassword(
            $passwordBroker,
            $resetService,
            $translator,
        );

        $result = $mutation(null, [
            'email'              => 'foo@bar.com',
            'reset_password_url' => [
                'url' => 'custom-url',
            ],
        ]);

        static::assertIsArray($result);
        static::assertCount(2, $result);
        static::assertSame('EMAIL_SENT', $result['status']);
        static::assertSame('translation', $result['message']);
    }
}
