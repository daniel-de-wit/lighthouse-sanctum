<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\GraphQL\Mutations;

use DanielDeWit\LighthouseSanctum\Contracts\Services\EmailVerificationServiceInterface;
use DanielDeWit\LighthouseSanctum\Traits\CreatesUserProvider;
use Exception;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Nuwave\Lighthouse\Exceptions\ValidationException;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use RuntimeException;

class VerifyEmail
{
    use CreatesUserProvider;

    public function __construct(
        protected AuthManager $authManager,
        protected Config $config,
        protected ValidationFactory $validationFactory,
        protected EmailVerificationServiceInterface $emailVerificationService,
    ) {}

    /**
     * @param  array<string, string|int>  $args
     * @return array<string, string>
     *
     * @throws Exception
     */
    public function __invoke(mixed $_, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array
    {
        $userProvider = $this->createUserProvider();

        $user = $userProvider->retrieveById($args['id']);

        if (! $user) {
            $this->emailVerificationService->throwAuthenticationException();
        }

        if (! $user instanceof MustVerifyEmail) {
            throw new RuntimeException('User must implement "'.MustVerifyEmail::class.'".');
        }

        if ($this->config->get('lighthouse-sanctum.use_signed_email_verification_url') === true) {
            $this->validateRequiredSignedArguments($args, implode('.', $resolveInfo->path));

            $this->emailVerificationService->verifySigned(
                $user,
                (string) $args['hash'],
                (int) $args['expires'],
                (string) $args['signature'],
            );
        } else {
            $this->emailVerificationService->verify($user, (string) $args['hash']);
        }

        $user->markEmailAsVerified();

        return [
            'status' => 'VERIFIED',
        ];
    }

    /**
     * @param  array<string, string|int>  $args
     *
     * @throws ValidationException
     */
    protected function validateRequiredSignedArguments(array $args, string $path): void
    {
        $validator = $this->validationFactory->make($args, [
            'expires'   => ['required'],
            'signature' => ['required'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException(sprintf('Validation failed for the field [%s].', $path), $validator);
        }
    }

    protected function getAuthManager(): AuthManager
    {
        return $this->authManager;
    }

    protected function getConfig(): Config
    {
        return $this->config;
    }
}
