<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\GraphQL\Mutations;

use DanielDeWit\LighthouseSanctum\Contracts\Services\ResetPasswordServiceInterface;
use DanielDeWit\LighthouseSanctum\Exceptions\GraphQLValidationException;
use Exception;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Support\Arr;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class ResetPassword
{
    public function __construct(
        protected PasswordBroker $passwordBroker,
        protected Translator $translator,
        protected ResetPasswordServiceInterface $resetPasswordService,
    ) {}

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, string>
     *
     * @throws Exception
     */
    public function __invoke(mixed $_, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array
    {
        $credentials = Arr::except($args, [
            'directive',
            'password_confirmation',
        ]);

        /** @var string $response */
        $response = $this->passwordBroker->reset($credentials, function (Authenticatable $user, string $password): void {
            $this->resetPasswordService->resetPassword($user, $password);
        });

        /** @var string $message */
        $message = $this->translator->get($response);

        if ($response === PasswordBroker::PASSWORD_RESET) {
            return [
                'status'  => 'PASSWORD_RESET',
                'message' => $message,
            ];
        }

        throw new GraphQLValidationException(
            $message,
            $this->getInvalidField($response),
            $resolveInfo,
        );
    }

    protected function getInvalidField(string $response): string
    {
        return match ($response) {
            PasswordBroker::INVALID_USER  => 'email',
            PasswordBroker::INVALID_TOKEN => 'token',
            default                       => '',
        };
    }
}
