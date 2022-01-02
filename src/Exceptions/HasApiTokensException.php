<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Exceptions;

use Exception;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\Contracts\HasApiTokens;

class HasApiTokensException extends Exception
{
    /**
     * @param Authenticatable|Model $user
     */
    public function __construct($user, Translator $translator)
    {
        parent::__construct($translator->get(
            "lighthouse-sanctum::exception.has_api_tokens_exception",
            ["userClass"=>get_class($user),"apiTokenClass"=>HasApiTokens::class]
        ));
    }
}
