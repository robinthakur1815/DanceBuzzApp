<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class CampaignType extends Enum
{
    const Open = 1;
    const DB = 2;
    const Vendor = 3;
    const School = 4;
}
