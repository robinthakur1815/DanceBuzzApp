<?php

namespace App\Helpers;

use App\Adapters\Subscription\SubscriptionAdapter;
use App\Enums\SubscriptionModule;

final class TaxFeeHelper
{
    public static function getTaxCalculationData($vendorId, $amount, $collectionType = SubscriptionModule::All, $stateId = null, $subscription_included = false)
    {
        $sa = new SubscriptionAdapter($vendorId);
        $amountData = $sa->process($amount, $collectionType, $stateId, $subscription_included);

        return $amountData;
    }
}
