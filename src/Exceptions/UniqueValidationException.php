<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Exceptions;

use Exception;
use Nuwave\Lighthouse\Exceptions\RendersErrorsExtensions;
use Nuwave\Lighthouse\Exceptions\ValidationException;

class UniqueValidationException extends Exception implements RendersErrorsExtensions
{
    protected string $validationMessage;

    public function __construct(string $message, string $path)
    {
        $this->validationMessage = $message;

        parent::__construct("Validation failed for the field [{$path}].");
    }

    public function isClientSafe(): bool
    {
        return true;
    }

    public function getCategory(): string
    {
        return ValidationException::CATEGORY;
    }

    public function extensionsContent(): array
    {
        return [
            'validation' => [
                'input' => [$this->validationMessage],
            ],
        ];
    }
}
