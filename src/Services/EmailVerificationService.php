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

    protected SignatureServiceInterface $signatureService;

    protected int $expiresIn;

    public function __construct(SignatureServiceInterface $signatureService, int $expiresIn)
    {
        $this->signatureService = $signatureService;
        $this->expiresIn        = $expiresIn;
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
        VerifyEmail::createUrlUsing(function (MustVerifyEmail $user) use ($url) {
            return $this->transformUrl($user, $url);
        });
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
        } catch (InvalidSignatureException $exception) {
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
     * @return mixed[]
     */
    protected function createUrlParameters(MustVerifyEmail $user): array
    {
        $parameters = [
            'id'      => $this->getModelFromUser($user)->getKey(),
            'hash'    => $this->createHash($user),
            'expires' => $this->createExpires(),
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
