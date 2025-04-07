<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Services;

use Carbon\Carbon;
use DanielDeWit\LighthouseSanctum\Contracts\Services\EmailVerificationServiceInterface;
use DanielDeWit\LighthouseSanctum\Contracts\Services\SignatureServiceInterface;
use DanielDeWit\LighthouseSanctum\Traits\HasUserModel;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Nuwave\Lighthouse\Exceptions\AuthenticationException;

class EmailVerificationService implements EmailVerificationServiceInterface
{
    use HasUserModel;

    public function __construct(
        protected SignatureServiceInterface $signatureService,
        protected int $expiresIn,
    ) {
        //
    }

    public function transformUrl(MustVerifyEmail $user, string $url): string
    {
        $parameters = $this->createUrlParameters($user);

        return str_replace([
            '__ID__',
            '__HASH__',
            '__EXPIRES__',
            '__SIGNATURE__',
        ], $parameters, $url);
    }

    public function setVerificationUrl(string $url): void
    {
        VerifyEmail::createUrlUsing(fn (MustVerifyEmail $user): string => $this->transformUrl($user, $url));
    }

    /**
     * @throws AuthenticationException
     */
    public function verify(MustVerifyEmail $user, string $hash): void
    {
        if (! hash_equals($hash, $this->createHash($user))) {
            $this->throwAuthenticationException();
        }
    }

    /**
     * @throws AuthenticationException
     */
    public function verifySigned(MustVerifyEmail $user, string $hash, int $expires, string $signature): void
    {
        $this->verify($user, $hash);

        if ($expires < Carbon::now()->getTimestamp()) {
            $this->throwAuthenticationException();
        }

        try {
            $this->signatureService->verify([
                'id'      => $this->getModelFromUser($user)->getKey(),
                'hash'    => $hash,
                'expires' => $expires,
            ], $signature);
        } catch (InvalidSignatureException) {
            $this->throwAuthenticationException();
        }
    }

    /**
     * @throws AuthenticationException
     */
    public function throwAuthenticationException(): void
    {
        throw new AuthenticationException('The provided input is incorrect.');
    }

    /**
     * @return list<string>
     */
    protected function createUrlParameters(MustVerifyEmail $user): array
    {
        $id = $this->getModelFromUser($user)->getKey();

        assert(is_int($id) || is_string($id));

        $parameters = [
            'id'      => (string) $id,
            'hash'    => $this->createHash($user),
            'expires' => (string) $this->createExpires(),
        ];

        $signature = $this->signatureService->generate($parameters);

        $values   = array_values($parameters);
        $values[] = $signature;

        return $values;
    }

    protected function createHash(MustVerifyEmail $user): string
    {
        return sha1($user->getEmailForVerification());
    }

    protected function createExpires(): int
    {
        return Carbon::now()
            ->addMinutes($this->expiresIn)
            ->getTimestamp();
    }
}
