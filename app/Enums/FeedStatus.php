<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 */
final class FeedStatus extends Enum
{
    const Active = 1;
    const Trashed = 0;
}
