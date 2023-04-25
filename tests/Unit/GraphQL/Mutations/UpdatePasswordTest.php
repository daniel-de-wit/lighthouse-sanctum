<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Tests\Unit\GraphQL\Mutations;

use DanielDeWit\LighthouseSanctum\Exceptions\GraphQLValidationException;
use DanielDeWit\LighthouseSanctum\GraphQL\Mutations\UpdatePassword;
use DanielDeWit\LighthouseSanctum\Tests\Traits\MocksAuthFactory;
use DanielDeWit\LighthouseSanctum\Tests\Unit\AbstractUnitTestCase;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Foundation\Auth\User;
use Mockery;
use Mockery\MockInterface;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use RuntimeException;

class UpdatePasswordTest extends AbstractUnitTestCase
{
    use MocksAuthFactory;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_updates_the_password(): void
    {
        /** @var User|MockInterface $user */
        $user = Mockery::mock(User::class)
            ->shouldReceive('getAuthPassword')
            ->twice()
            ->andReturn('password-hash')
            ->getMock()
            ->shouldReceive('update')
            ->once()
            ->with([
                'password' => 'new-password-hash',
            ])
            ->getMock();

        /** @var Hasher|MockInterface $hasher */
        $hasher = Mockery::mock(Hasher::class)
            ->shouldReceive('check')
            ->once()
            ->with('current-password', 'password-hash')
            ->andReturnTrue()
            ->getMock()
            ->shouldReceive('check')
            ->once()
            ->with('new-password', 'password-hash')
            ->andReturnFalse()
            ->getMock()
            ->shouldReceive('make')
            ->once()
            ->with('new-password')
            ->andReturn('new-password-hash')
            ->getMock();

        $mutation = new UpdatePassword(
            $this->mockAuthFactory($user),
            $hasher,
            Mockery::mock(Translator::class),
        );

        $result = $mutation(
            null,
            [
                'current_password' => 'current-password',
                'password'         => 'new-password',
            ],
            Mockery::mock(GraphQLContext::class),
            $this->mockResolveInfo(),
        );

        static::assertSame('PASSWORD_UPDATED', $result['status']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_throws_an_exception_if_no_user_is_found_by_the_guard(): void
    {
        static::expectException(RuntimeException::class);
        static::expectExceptionMessage('Unable to detect current user.');

        $mutation = new UpdatePassword(
            $this->mockAuthFactory(),
            Mockery::mock(Hasher::class),
            Mockery::mock(Translator::class),
        );

        $mutation(
            null,
            [],
            Mockery::mock(GraphQLContext::class),
            $this->mockResolveInfo(),
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_throws_an_exception_if_the_current_password_is_different(): void
    {
        static::expectException(GraphQLValidationException::class);
        static::expectExceptionMessage('Validation failed for the field [some.path].');

        /** @var User|MockInterface $user */
        $user = Mockery::mock(User::class)
            ->shouldReceive('getAuthPassword')
            ->once()
            ->andReturn('password-hash')
            ->getMock();

        /** @var Hasher|MockInterface $hasher */
        $hasher = Mockery::mock(Hasher::class)
            ->shouldReceive('check')
            ->once()
            ->with('current-password', 'password-hash')
            ->andReturnFalse()
            ->getMock();

        /** @var Translator|MockInterface $translator */
        $translator = Mockery::mock(Translator::class)
            ->shouldReceive('get')
            ->once()
            ->with('validation.same', [
                'attribute' => 'current_password',
                'other'     => 'user password',
            ])
            ->andReturn('error-message')
            ->getMock();

        $mutation = new UpdatePassword(
            $this->mockAuthFactory($user),
            $hasher,
            $translator,
        );

        $mutation(
            null,
            [
                'current_password' => 'current-password',
                'password'         => 'new-password',
            ],
            Mockery::mock(GraphQLContext::class),
            $this->mockResolveInfo(),
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_throws_an_exception_if_the_new_password_is_the_same(): void
    {
        static::expectException(GraphQLValidationException::class);
        static::expectExceptionMessage('Validation failed for the field [some.path].');

        /** @var User|MockInterface $user */
        $user = Mockery::mock(User::class)
            ->shouldReceive('getAuthPassword')
            ->twice()
            ->andReturn('password-hash')
            ->getMock();

        /** @var Hasher|MockInterface $hasher */
        $hasher = Mockery::mock(Hasher::class)
            ->shouldReceive('check')
            ->once()
            ->with('current-password', 'password-hash')
            ->andReturnTrue()
            ->getMock()
            ->shouldReceive('check')
            ->once()
            ->with('new-password', 'password-hash')
            ->andReturnTrue()
            ->getMock();

        /** @var Translator|MockInterface $translator */
        $translator = Mockery::mock(Translator::class)
            ->shouldReceive('get')
            ->once()
            ->with('validation.different', [
                'attribute' => 'password',
                'other'     => 'user password',
            ])
            ->andReturn('error-message')
            ->getMock();

        $mutation = new UpdatePassword(
            $this->mockAuthFactory($user),
            $hasher,
            $translator,
        );

        $mutation(
            null,
            [
                'current_password' => 'current-password',
                'password'         => 'new-password',
            ],
            Mockery::mock(GraphQLContext::class),
            $this->mockResolveInfo(),
        );
    }

    protected function mockResolveInfo(): \GraphQL\Type\Definition\ResolveInfo|\Mockery\MockInterface
    {
        /** @var ResolveInfo|MockInterface $resolveInfo */
        $resolveInfo = Mockery::mock(ResolveInfo::class);

        $resolveInfo->path = ['some', 'path'];

        return $resolveInfo;
    }
}
