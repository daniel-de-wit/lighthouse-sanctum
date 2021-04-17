<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Tests\Traits;

use Illuminate\Testing\TestResponse;

trait AssertsGraphQLErrorMessage
{
    public static function assertGraphQLErrorMessage(TestResponse $response, string $message): void
    {
        static::assertContains(
            $message,
            $response->json('errors.*.message'),
            "The response should contain the following error message: '{$message}'",
        );
    }

    abstract public static function assertContains($needle, iterable $haystack, string $message = ''): void;
}
