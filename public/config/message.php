<?php

/*
|--------------------------------------------------------------------------
| Laravel PHP Facade/Wrapper for the Youtube Data API v3
|--------------------------------------------------------------------------
|
| Here is where you can set your key for Youtube API. In case you do not
| have it, it can be acquired from: https://console.developers.google.com
*/

return [
    'event' => [
        'title'       => 'New event created',
        'description' => 'New event created',

        'reminder_title'       => 'Event Reminder',
        'reminder_description' => 'Your Event %s is going to start on %s',
    ],

//     Your live class "{class name}" is going to start on Jan 02, 2020 at 10:00PM.

// Heading - Live Class Reminder

    'class' => [
        'title'       => 'New class created',
        'description' => 'New class created',

        'reminder_title'       => 'Class Reminder',
        'reminder_description' => 'Your class %s is going to start on %s',
    ],

    'workshop' => [
        'title'       => 'New workshop created',
        'description' => 'New workshop created',

        'reminder_title'       => 'Workshop Reminder',
        'reminder_description' => 'Your workshop %s is going to start on %s',
    ],

    'liveClass' => [
        'title'       => 'New live class created',
        'description' => 'new live class created',

        'reminder_title'       => 'Live Class Reminder',
        'reminder_description' => 'Your live class %s is going to start on %s',
    ],

    'booking' => [
        'partner_title'       => '%s Payment Received',
        'partner_description' => 'You have received a payment of %s for %s %s on %s. The transaction ID is %ss',

        'client_title'       => '%s Booked',
        'client_description' => 'You have made a payment of %s for %s %s on %s. Your transaction ID is %s',
    ],

    'feed' => [
        'title'       => 'New Feed',
        'description' => '%s',
    ],

    'campaign' => [
        'title'       => 'new campaign created',
        'description' => 'new campaign created',
    ],
    'story' => [
        'title'         =>   'Entry Rejected',
        'description'   =>   'Your entry for %s has been rejected for %s.
Reason: %s'
    ],

    'accthed_student' => "You have been enrolled by  %s for  %s. Please find the details below",
    
    'sms_live_class' => "Hello %s, Congratulations on your first virtual Live class session! Download the dancebuzz app and log in with your username and password.Link: link:" .env("SHORT_FRIEND_URL", "https://mykc.in/__6kIg") . ".Queries contact: support@dancebuzz.com. The dancebuzz Team"
];
