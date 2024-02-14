<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class LiveClassStatus extends Enum
{
    const Active = 1;
    const Suspended = 2;
    const ReActivate = 3;
    const Expired = 4;
    const Running = 5;
    const Completed = 6;
    const ReSchedule = 7;
}
