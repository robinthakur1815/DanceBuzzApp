<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class SpamReportStatus extends Enum
{
    const Pending = 1;
    const Approved = 2;
}
