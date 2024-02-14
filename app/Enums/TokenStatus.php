<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class TokenStatus extends Enum
{
    const Active = 1;
    const Revoke = 2;
}
