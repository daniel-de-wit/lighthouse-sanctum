<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Tests\Unit\Services;

use DanielDeWit\LighthouseSanctum\Services\ResetPasswordService;
use DanielDeWit\LighthouseSanctum\Tests\Unit\AbstractUnitTestCase;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Foundation\Auth\User;
use Mockery;
use Mockery\MockInterface;

class ResetPasswordServiceTest extends AbstractUnitTestCase
{
    /**
     * @test
     */
    public function it_resets_a_password(): void
    {
        /** @var User|MockInterface $user */
        $user = Mockery::mock(User::class)
            ->shouldReceive('setAttribute')
            ->once()
            ->with('password', 'some-hash')
            ->getMock()
            ->shouldReceive('save')
            ->once()
            ->getMock();

        /** @var Hasher|MockInterface $hasher */
        $hasher = Mockery::mock(Hasher::class)
            ->shouldReceive('make')
            ->once()
            ->with('some-password')
            ->andReturn('some-hash')
            ->getMock();

        /** @var Dispatcher|MockInterface $dispatcher */
        $dispatcher = Mockery::mock(Dispatcher::class)
            ->shouldReceive('dispatch')
            ->once()
            ->withArgs(function (PasswordReset $event) use ($user) {
                return $event->user === $user;
            })
            ->getMock();

        $service = new ResetPasswordService($hasher, $dispatcher);

        $service->resetPassword($user, 'some-password');
    }
}
