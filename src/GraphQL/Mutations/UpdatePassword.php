<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\GraphQL\Mutations;

use DanielDeWit\LighthouseSanctum\Exceptions\GraphQLValidationException;
use DanielDeWit\LighthouseSanctum\Traits\HasAuthenticatedUser;
use DanielDeWit\LighthouseSanctum\Traits\HasUserModel;
use Exception;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Contracts\Translation\Translator;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class UpdatePassword
{
    use HasAuthenticatedUser;
    use HasUserModel;

    protected ResolveInfo $resolveInfo;

    public function __construct(
        protected AuthFactory $authFactory,
        protected Hasher $hasher,
        protected Translator $translator,
    ) {
    }

    /**
     * @param  array<string, string>  $args
     * @return array<string, string>
     *
     * @throws Exception
     */
    public function __invoke(mixed $_, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array
    {
        $this->resolveInfo = $resolveInfo;

        $user = $this->getAuthenticatedUser();

        $this->currentPasswordMustBeTheSame($user, $args['current_password']);
        $this->newPasswordMustBeDifferent($user, $args['password']);

        $this->getModelFromUser($user)->update([
            'password' => $this->hasher->make($args['password']),
        ]);

        return [
            'status' => 'PASSWORD_UPDATED',
        ];
    }

    /**
     * @throws GraphQLValidationException
     */
    protected function currentPasswordMustBeTheSame(Authenticatable $user, string $currentPassword): void
    {
        if (! $this->hasher->check($currentPassword, $user->getAuthPassword())) {
            /** @var string $message */
            $message = $this->translator->get('validation.same', [
                'attribute' => 'current_password',
                'other'     => 'user password',
            ]);

            throw new GraphQLValidationException($message, 'current_password', $this->resolveInfo);
        }
    }

    /**
     * @throws GraphQLValidationException
     */
    protected function newPasswordMustBeDifferent(Authenticatable $user, string $newPassword): void
    {
        if ($this->hasher->check($newPassword, $user->getAuthPassword())) {
            /** @var string $message */
            $message = $this->translator->get('validation.different', [
                'attribute' => 'password',
                'other'     => 'user password',
            ]);

            throw new GraphQLValidationException($message, 'password', $this->resolveInfo);
        }
    }

    protected function getAuthFactory(): AuthFactory
    {
        return $this->authFactory;
    }
}
