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
    protected PasswordBroker $passwordBroker;
    protected ResetPasswordServiceInterface $resetPasswordService;
    protected Translator $translator;


    public function __construct(
        PasswordBroker $passwordBroker,
        ResetPasswordServiceInterface $resetPasswordService,
        Translator $translator
    ) {
        $this->passwordBroker       = $passwordBroker;
        $this->resetPasswordService = $resetPasswordService;
        $this->translator           = $translator;
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
            $this->resetPasswordService->resetPassword($user, $password);
        });

        if ($response === PasswordBroker::PASSWORD_RESET) {
            return [
                'status'  => 'PASSWORD_RESET',
                'message' => $this->translator->get("lighthouse-sanctum::".$response),
            ];
        }

        throw new GraphQLValidationException(
            $this->translator->get("lighthouse-sanctum::".$response),
            $this->getInvalidField($response),
            $resolveInfo,
            $this->translator
        );
    }

    protected function getInvalidField(string $response): string
    {
        switch ($response) {
            case PasswordBroker::INVALID_USER:
                return $this->translator->get("lighthouse-sanctum::passwords.attributes.email");

            case PasswordBroker::INVALID_TOKEN:
                return $this->translator->get("lighthouse-sanctum::passwords.attributes.token");

            default:
                return '';
        }
    }
}
