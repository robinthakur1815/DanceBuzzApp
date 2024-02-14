<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class AlignmentType extends Enum
{
    const LeftAlignImage = 1;
    const CenterAlignImage = 2;
    const RightAlignImage = 3;
    const TextUpside = 4;
    const WithoutImage = 5;
    const CollectionBox = 6;
    const HomeBanner = 7;
    const ImagedHighlightBox = 8;
    const NoImageHighLightBox = 9;
    const FeaturedCollection = 10;
    const CollectionList = 11;
    const LeftImageBrandSection = 12;
}
