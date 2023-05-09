<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\GraphQL\Mutations;

use DanielDeWit\LighthouseSanctum\Contracts\Services\ResetPasswordServiceInterface;
use Exception;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Contracts\Translation\Translator;

class ForgotPassword
{
    public function __construct(
        protected PasswordBroker $passwordBroker,
        protected ResetPasswordServiceInterface $resetPasswordService,
        protected Translator $translator,
    ) {
        //
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, string>
     *
     * @throws Exception
     */
    public function __invoke(mixed $_, array $args): array
    {
        if (isset($args['reset_password_url'])) {
            /** @var array<string, string> $resetPasswordUrl */
            $resetPasswordUrl = $args['reset_password_url'];

            $this->resetPasswordService->setResetPasswordUrl($resetPasswordUrl['url']);
        }

        $this->passwordBroker->sendResetLink([
            'email' => $args['email'],
        ]);

        /** @var string $message */
        $message = $this->translator->get('An email has been sent');

        return [
            'status'  => 'EMAIL_SENT',
            'message' => $message,
        ];
    }
}
