<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Services;

use DanielDeWit\LighthouseSanctum\Contracts\Services\ResetPasswordServiceInterface;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\Eloquent\Model;

class ResetPasswordService implements ResetPasswordServiceInterface
{
    public function __construct(
        protected Hasher $hash,
        protected Dispatcher $dispatcher,
    ) {}

    public function transformUrl(CanResetPassword $notifiable, string $token, string $url): string
    {
        return str_replace([
            '__EMAIL__',
            '__TOKEN__',
        ], [
            rawurlencode($notifiable->getEmailForPasswordReset()),
            rawurlencode($token),
        ], $url);
    }

    public function setResetPasswordUrl(string $url): void
    {
        ResetPasswordNotification::createUrlUsing(fn (CanResetPassword $notifiable, string $token): string => $this->transformUrl($notifiable, $token, $url));
    }

    public function resetPassword($user, string $password): void
    {
        /** @var Model $user */
        $this->updateUser($user, $password);

        /** @var Authenticatable $user */
        $this->dispatcher->dispatch(new PasswordReset($user));
    }

    protected function updateUser(Model $user, string $password): void
    {
        $user->setAttribute('password', $this->hash->make($password));
        $user->save();
    }
}
