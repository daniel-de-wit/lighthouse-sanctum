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

    protected AuthFactory $authFactory;
    protected Hasher $hasher;
    protected ResolveInfo $resolveInfo;
    protected Translator $translator;


    public function __construct(AuthFactory $authFactory, Hasher $hasher, Translator $translator)
    {
        $this->authFactory = $authFactory;
        $this->hasher      = $hasher;
        $this->translator  = $translator;
    }

    /**
     * @param mixed $_
     * @param array<string, mixed> $args
     * @param GraphQLContext $context
     * @param ResolveInfo $resolveInfo
     * @return array<string, string>
     * @throws Exception
     */
    public function __invoke($_, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array
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
     * @param Authenticatable $user
     * @param string $currentPassword
     * @throws GraphQLValidationException
     */
    protected function currentPasswordMustBeTheSame(Authenticatable $user, string $currentPassword): void
    {
        if (! $this->hasher->check($currentPassword, $user->getAuthPassword())) {
            $message = $this->translator->get('lighthouse-sanctum::validation.same', [
                'attribute' => $this->translator->get('lighthouse-sanctum::validation.attributes.current_password'),
                'other'     => $this->translator->get('lighthouse-sanctum::validation.attributes.user_password'),
            ]);

            throw new GraphQLValidationException(
                $message,
                $this->translator->get("lighthouse-sanctum::validation.attributes.current_password"),
                $this->resolveInfo,
                $this->translator
            );
        }
    }

    /**
     * @param Authenticatable $user
     * @param string $newPassword
     * @throws GraphQLValidationException
     */
    protected function newPasswordMustBeDifferent(Authenticatable $user, string $newPassword): void
    {
        if ($this->hasher->check($newPassword, $user->getAuthPassword())) {
            $message = $this->translator->get('lighthouse-sanctum::validation.different', [
                'attribute' => $this->translator->get('lighthouse-sanctum::validation.attributes.password'),
                'other'     => $this->translator->get('lighthouse-sanctum::validation.attributes.user_password'),
            ]);

            throw new GraphQLValidationException(
                $message,
                $this->translator->get("lighthouse-sanctum::validation.attributes.password"),
                $this->resolveInfo,
                $this->translator
            );
        }
    }

    protected function getAuthFactory(): AuthFactory
    {
        return $this->authFactory;
    }
}
