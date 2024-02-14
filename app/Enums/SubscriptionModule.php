<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class SubscriptionModule extends Enum
{
    // Following should be exactly same as CollectionType enum
    const Events = 11;
    const Classes = 25;
    const Workshops = 26;
    const LiveClasses = 37;

    // Add more classes
    const All = 1;
}
