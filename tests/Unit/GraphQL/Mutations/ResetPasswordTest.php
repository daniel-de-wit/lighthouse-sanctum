<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Tests\Unit\GraphQL\Mutations;

use Closure;
use DanielDeWit\LighthouseSanctum\Contracts\Services\ResetPasswordServiceInterface;
use DanielDeWit\LighthouseSanctum\Exceptions\GraphQLValidationException;
use DanielDeWit\LighthouseSanctum\GraphQL\Mutations\ResetPassword;
use DanielDeWit\LighthouseSanctum\Tests\Unit\AbstractUnitTestCase;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Foundation\Auth\User;
use Mockery;
use Mockery\MockInterface;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class ResetPasswordTest extends AbstractUnitTestCase
{
    /**
     * @test
     */
    public function it_resets_a_password(): void
    {
        /** @var User|MockInterface $user */
        $user = Mockery::mock(User::class);

        /** @var PasswordBroker|MockInterface $passwordBroker */
        $passwordBroker = Mockery::mock(PasswordBroker::class)
            ->shouldReceive('reset')
            ->withArgs(function (array $credentials, Closure $callback) use ($user) {
                $callback($user, 'supersecret');

                return empty(array_diff($credentials, [
                    'email'    => 'foo@bar.com',
                    'token'    => '1234567890',
                    'password' => 'supersecret',
                ]));
            })
            ->andReturn('passwords.reset')
            ->getMock();

        /** @var Translator|MockInterface $translator */
        $translator = Mockery::mock(Translator::class)
            ->shouldReceive('get')
            ->with('passwords.reset')
            ->andReturn('response-translation')
            ->getMock();

        /** @var ResetPasswordServiceInterface|MockInterface $resetPasswordService */
        $resetPasswordService = Mockery::mock(ResetPasswordServiceInterface::class)
            ->shouldReceive('resetPassword')
            ->with($user, 'supersecret')
            ->getMock();

        $mutation = new ResetPassword(
            $passwordBroker,
            $translator,
            $resetPasswordService,
        );

        $result = $mutation(null, [
            'email'                 => 'foo@bar.com',
            'token'                 => '1234567890',
            'password'              => 'supersecret',
            'password_confirmation' => 'supersecret',
        ], Mockery::mock(GraphQLContext::class), Mockery::mock(ResolveInfo::class));

        static::assertIsArray($result);
        static::assertCount(2, $result);
        static::assertSame('PASSWORD_RESET', $result['status']);
        static::assertSame('response-translation', $result['message']);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_if_the_reset_failed(): void
    {
        static::expectException(GraphQLValidationException::class);
        static::expectExceptionMessage('Validation failed for the field [some.dotted.path].');

        /** @var PasswordBroker|MockInterface $passwordBroker */
        $passwordBroker = Mockery::mock(PasswordBroker::class)
            ->shouldReceive('reset')
            ->withArgs(function (array $credentials, Closure $callback) {
                return empty(array_diff($credentials, [
                    'email'    => 'foo@bar.com',
                    'token'    => '1234567890',
                    'password' => 'supersecret',
                ]));
            })
            ->andReturn('some-error')
            ->getMock();

        /** @var Translator|MockInterface $translator */
        $translator = Mockery::mock(Translator::class)
            ->shouldReceive('get')
            ->with('some-error')
            ->andReturn('error-translation')
            ->getMock();

        /** @var ResetPasswordServiceInterface|MockInterface $resetPasswordService */
        $resetPasswordService = Mockery::mock(ResetPasswordServiceInterface::class)
            ->shouldNotReceive('resetPassword')
            ->getMock();

        $resolveInfo       = Mockery::mock(ResolveInfo::class);
        $resolveInfo->path = ['some', 'dotted', 'path'];

        $mutation = new ResetPassword(
            $passwordBroker,
            $translator,
            $resetPasswordService,
        );

        $mutation(null, [
            'email'                 => 'foo@bar.com',
            'token'                 => '1234567890',
            'password'              => 'supersecret',
            'password_confirmation' => 'supersecret',
        ], Mockery::mock(GraphQLContext::class), $resolveInfo);
    }
}
