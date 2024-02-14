<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class Recurrence extends Enum
{
    const Never = 1;
    const Daily = 2;
    const EveryWeek = 3;
    const EveryWeekend = 4;
    const EveryMonth = 5;
    const Custom = 6;
}
