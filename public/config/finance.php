<?php

use App\Enums\SubscriptionType;

return [

    'default_currency' => 'INR',
    'max_base_amount' => 200000,

    'online_taxes' => [
        ['name' => 'CGST', 'value' =>  0],
        ['name' => 'SGST', 'value' =>  0],
        ['name' => 'IGST', 'value' =>  18],
    ],

    'payment_gateway' => [
        'charge' => 1.75,
    ],

    'default_vendor_subscription' => [
        'charge' => 3.0,
        'type' => SubscriptionType::PercentageCharge,
    ],

];
