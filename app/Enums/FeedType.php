<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 */
final class FeedType extends Enum
{
    const NormalFeeds = 1;
    const YouTubeFeeds = 2;
}
