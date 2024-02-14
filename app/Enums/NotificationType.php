<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class NotificationType extends Enum
{
    const Custom = 1;
    const EventCollection = 2;
    const ClassCollection = 3;
    const WorkShopCollection = 4;
    const NewFeed = 5;
    const FeedLike = 6;
    const NewComment = 7;
    const CollectionBookByClient = 8;
    const CollectionBooked = 9;
    const Campaign = 10;
    const LivesClassCollection = 11;
    const StoryRejection = 12;
}
