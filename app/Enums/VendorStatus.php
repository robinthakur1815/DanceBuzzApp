<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class VendorStatus extends Enum
{
    const OnBoard = 1;
    const Active = 2;
    const Pending = 3;
    const Rejected = 4;
}
