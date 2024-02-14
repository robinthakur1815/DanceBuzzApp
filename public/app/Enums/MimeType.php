<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class MimeType extends Enum
{
    const Image = 1;
    const Video = 2;
    const Audio = 3;
    const Docs = 4;
}
