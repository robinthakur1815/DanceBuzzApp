<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class StoryStatus extends Enum
{
    const Submitted = 1;
    const ShortListed = 2;
    const Rejected = 3;
    const Shopable = 4;
    const Winner = 5;
}
