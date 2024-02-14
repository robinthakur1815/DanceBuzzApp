<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class FeedbackType extends Enum
{
    const Bug = 1;
    const Suggestion = 2;
    const Request = 3;
}
