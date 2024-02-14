<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class ReminderType extends Enum
{
    const Classes = 1;
    const LiveClass = 2;
    const WorkshopEvents = 3;
}
