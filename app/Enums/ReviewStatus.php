<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class ReviewStatus extends Enum
{
    const Submitted = 1;
    const Approved = 2;
    const Disapprove = 3;
}
