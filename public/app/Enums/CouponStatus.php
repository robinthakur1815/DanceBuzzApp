<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class CouponStatus extends Enum
{
    const Submitted = 1;
    const Draft = 2;
    const Published = 3;
}
