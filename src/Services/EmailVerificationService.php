<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Services;

use Carbon\Carbon;
use DanielDeWit\LighthouseSanctum\Contracts\Services\EmailVerificationServiceInterface;
use DanielDeWit\LighthouseSanctum\Contracts\Services\SignatureServiceInterface;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Nuwave\Lighthouse\Exceptions\AuthenticationException;
use RuntimeException;

class EmailVerificationService implements EmailVerificationServiceInterface
{
    protected SignatureServiceInterface $signatureService;
    protected int $expiresIn;

    public function __construct(SignatureServiceInterface $signatureService, int $expiresIn)
    {
        $this->signatureService = $signatureService;
        $this->expiresIn        = $expiresIn;
    }

    public function setVerificationUrl(string $url): void
    {
        VerifyEmail::createUrlUsing(function ($user) use ($url) {
            $parameters = $this->createUrlParameters($user);

            return str_replace([
                '__ID__',
                '__HASH__',
                '__EXPIRES__',
                '__SIGNATURE__',
            ], $parameters, $url);
        });
    }

    /**
     * @param MustVerifyEmail $user
     * @param string          $hash
     * @throws AuthenticationException
     */
    public function verify(MustVerifyEmail $user, string $hash): void
    {
        if (! hash_equals($hash, $this->createHash($user))) {
            $this->throwAuthenticationException();
        }
    }

    /**
     * @param MustVerifyEmail $user
     * @param string          $hash
     * @param int             $expires
     * @param string          $signature
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
     * @param MustVerifyEmail $user
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

    protected function getModelFromUser(MustVerifyEmail $user): Model
    {
        if (! $user instanceof Model) {
            throw new RuntimeException('The class implementing "' . MustVerifyEmail::class . '" must extend "' . Model::class . '".');
        }

        return $user;
    }
}
