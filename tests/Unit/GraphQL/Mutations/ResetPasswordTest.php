<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Tests\Unit\GraphQL\Mutations;

use Closure;
use DanielDeWit\LighthouseSanctum\Enums\ResetPasswordStatus;
use DanielDeWit\LighthouseSanctum\Exceptions\ResetPasswordException;
use DanielDeWit\LighthouseSanctum\GraphQL\Mutations\ResetPassword;
use DanielDeWit\LighthouseSanctum\Tests\Unit\AbstractUnitTest;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Foundation\Auth\User;
use Mockery;
use Mockery\MockInterface;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class ResetPasswordTest extends AbstractUnitTest
{
    /**
     * @test
     */
    public function it_resets_a_password(): void
    {
        /** @var User|MockInterface $user */
        $user = Mockery::mock(User::class)
            ->shouldReceive('setAttribute')
            ->with('password', 'some-hash')
            ->andReturnSelf()
            ->getMock()
            ->shouldReceive('save')
            ->getMock();

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

        /** @var Hasher|MockInterface $hash */
        $hash = Mockery::mock(Hasher::class)
            ->shouldReceive('make')
            ->with('supersecret')
            ->andReturn('some-hash')
            ->getMock();

        /** @var Dispatcher|MockInterface $dispatcher */
        $dispatcher = Mockery::mock(Dispatcher::class)
            ->shouldReceive('dispatch')
            ->withArgs(function (PasswordReset $event) use ($user) {
                return $event->user === $user;
            })
            ->getMock();

        /** @var Translator|MockInterface $translator */
        $translator = Mockery::mock(Translator::class)
            ->shouldReceive('get')
            ->with('passwords.reset')
            ->andReturn('response-translation')
            ->getMock();

        $mutation = new ResetPassword(
            $passwordBroker,
            $hash,
            $dispatcher,
            $translator,
        );

        $result = $mutation(null, [
            'email'                 => 'foo@bar.com',
            'token'                 => '1234567890',
            'password'              => 'supersecret',
            'password_confirmation' => 'supersecret',
        ], Mockery::mock(GraphQLContext::class), Mockery::mock(ResolveInfo::class));

        static::assertIsArray($result);
        static::assertCount(2, $result);
        static::assertTrue(ResetPasswordStatus::PASSWORD_RESET()->is($result['status']));
        static::assertSame('response-translation', $result['message']);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_if_the_reset_failed(): void
    {
        static::expectException(ResetPasswordException::class);
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

        $resolveInfo = Mockery::mock(ResolveInfo::class);
        $resolveInfo->path = ['some', 'dotted', 'path'];

        $mutation = new ResetPassword(
            $passwordBroker,
            Mockery::mock(Hasher::class),
            Mockery::mock(Dispatcher::class),
            $translator,
        );

        $mutation(null, [
            'email'                 => 'foo@bar.com',
            'token'                 => '1234567890',
            'password'              => 'supersecret',
            'password_confirmation' => 'supersecret',
        ], Mockery::mock(GraphQLContext::class), $resolveInfo);
    }
}
