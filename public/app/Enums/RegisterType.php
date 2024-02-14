<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class RegisterType extends Enum
{
    const Web = 1;
    const Ios = 2;
    const Android = 3;
}
