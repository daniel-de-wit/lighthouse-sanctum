<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Exceptions;

use Exception;
use GraphQL\Error\ClientAware;
use GraphQL\Error\ProvidesExtensions;
use GraphQL\Type\Definition\ResolveInfo;

class GraphQLValidationException extends Exception implements ClientAware, ProvidesExtensions
{
    protected string $validationMessage;

    protected string $field;

    public function __construct(string $message, string $field, string|ResolveInfo $path)
    {
        $this->validationMessage = $message;
        $this->field             = $field;

        if ($path instanceof ResolveInfo) {
            $path = implode('.', $path->path);
        }

        parent::__construct("Validation failed for the field [{$path}].");
    }

    public function isClientSafe(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<string, array<int, string>>>
     */
    public function getExtensions(): array
    {
        return [
            'validation' => [
                "input.{$this->field}" => [$this->validationMessage],
            ],
        ];
    }
}
