<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class AppUpgradeType extends Enum
{
    const Mandatory = 1;
    const Optional = 2;
    const Skip = 3;
}
