<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class PublishStatus extends Enum
{
    const Draft = 1;
    const Submitted = 2;
    const Published = 3;
}
