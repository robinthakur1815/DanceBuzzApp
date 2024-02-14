<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class SubscriptionType extends Enum
{
    const PercentageCharge = 1;
    const FlatCharge = 2;
    const NoCharge = 3;
}
