<?php

namespace DanielDeWit\LighthouseSanctum\Enums;

use BenSampo\Enum\Enum;

final class RegisterStatus extends Enum
{
    const MUST_VERIFY_EMAIL = 'MUST_VERIFY_EMAIL';
    const SUCCESS = 'SUCCESS';
}
