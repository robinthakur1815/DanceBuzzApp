<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class EmailType extends Enum
{
    const LiveClasses =   1;
    const Classes     =   2;
    const WorkShop    =   3;
    const Events      =   4;
}