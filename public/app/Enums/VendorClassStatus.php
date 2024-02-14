<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class VendorClassStatus extends Enum
{
    const Active = 1;
    const Pending = 2;
    const Deactivated = 3;
}
