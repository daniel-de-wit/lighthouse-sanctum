<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static MUST_VERIFY_EMAIL()
 * @method static static SUCCESS()
 */
final class RegisterStatus extends Enum
{
    const MUST_VERIFY_EMAIL = 'MUST_VERIFY_EMAIL';
    const SUCCESS           = 'SUCCESS';
}
