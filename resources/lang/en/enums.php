<?php
use App\Enums\CollectionType;
use App\Enums\MeetingProvider;
use \App\Enums\LiveClassStatus;

return [

    CollectionType::class => [
        CollectionType::events => 'Event',
        CollectionType::classes => 'Class',
        CollectionType::workshops => 'Workshop',
        CollectionType::classDeck => 'ClassDeck',
//        CollectionType::MySchool => 'My School'
    ],

//   MeetingProvider::class => [
//       MeetingProvider::None => 'None',
//       MeetingProvider::Dancebuzz => 'Dancebuzz Live Meeting',
//       MeetingProvider::Zoom => 'Zoom Meeting',
//       MeetingProvider::Msteam => 'Microsoft Teams'
//    ],
//
//   LiveClassStatus::class => [
//       LiveClassStatus::Active => 'Active',
//       LiveClassStatus::Running => 'Running',
//       LiveClassStatus::Completed => 'Completed',
//       LiveClassStatus::ReSchedule => 'Rescheduled',
//       LiveClassStatus::Expired => 'Expired',
//       LiveClassStatus::Suspended => 'Suspended',
//
//    ],

];
