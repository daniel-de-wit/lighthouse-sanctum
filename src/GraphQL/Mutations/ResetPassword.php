<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\GraphQL\Mutations;

use DanielDeWit\LighthouseSanctum\Exceptions\ResetPasswordException;
use Exception;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class ResetPassword
{
    protected PasswordBroker $passwordBroker;
    protected Hasher $hash;
    protected Dispatcher $dispatcher;
    protected Translator $translator;

    public function __construct(
        PasswordBroker $passwordBroker,
        Hasher $hash,
        Dispatcher $dispatcher,
        Translator $translator
    ) {
        $this->passwordBroker = $passwordBroker;
        $this->hash           = $hash;
        $this->dispatcher     = $dispatcher;
        $this->translator     = $translator;
    }

    /**
     * @param mixed $_
     * @param array<string, mixed> $args
     * @param GraphQLContext $context
     * @param ResolveInfo $resolveInfo
     * @return array<string, string|array>
     * @throws Exception
     */
    public function __invoke($_, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array
    {
        $credentials = Arr::except($args, [
            'directive',
            'password_confirmation',
        ]);

        $response = $this->passwordBroker->reset($credentials, function (Authenticatable $user, string $password) {
            $this->resetPassword($user, $password);

            $this->dispatcher->dispatch(new PasswordReset($user));
        });

        if ($response === PasswordBroker::PASSWORD_RESET) {
            return [
                'status'  => 'PASSWORD_RESET',
                'message' => $this->translator->get($response),
            ];
        }

        throw new ResetPasswordException(
            $this->translator->get($response),
            implode('.', $resolveInfo->path),
        );
    }

    protected function resetPassword(Authenticatable $user, string $password): void
    {
        /** @var Model $user */
        $user->setAttribute('password', $this->hash->make($password));
        $user->save();
    }
}
