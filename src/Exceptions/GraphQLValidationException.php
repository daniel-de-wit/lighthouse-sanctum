<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Exceptions;

use Exception;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Contracts\Translation\Translator;
use Nuwave\Lighthouse\Exceptions\RendersErrorsExtensions;

class GraphQLValidationException extends Exception implements RendersErrorsExtensions
{
    protected string $validationMessage;
    protected string $field;

    /**
     * @param string $message
     * @param string $field
     * @param string|ResolveInfo $path
     */
    public function __construct(string $message, string $field, $path, Translator $translator)
    {
        $this->validationMessage = $message;
        $this->field             = $field;

        if ($path instanceof ResolveInfo) {
            $path = implode('.', $path->path);
        }

        parent::__construct($translator->get("lighthouse-sanctum::exception.validation_exception", ["path"=>$path]));
    }

    public function isClientSafe(): bool
    {
        return true;
    }

    public function getCategory(): string
    {
        return 'validation';
    }

    public function extensionsContent(): array
    {
        return [
            'validation' => [
                "input.{$this->field}" => [$this->validationMessage],
            ],
        ];
    }
}
