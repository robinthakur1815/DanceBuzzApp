<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class PaymentStatus extends Enum
{
    const Received = 1;
    const Pending = 2;
    const Failed = 3;
    const Cancel = 4;
    const Processed = 5;
}
