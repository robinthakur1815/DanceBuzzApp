<?php

namespace App\Adapters\Subscription;

use App\Enums\SubscriptionModule;
use App\Enums\SubscriptionType;
use App\Helpers\UserHelper;
use App\Lib\Util;
use App\Role;
use App\Student;
use App\SubscriptionModel;
use App\User;
use Illuminate\Support\Facades\Facade;

class SubscriptionAdapter extends SubscriptionAdapterBase
{
    private $vendorId;

    public function __construct($vendorId)
    {
        $this->vendorId = $vendorId;
    }

    private function getSubscriptionModel($subscriptionModule)
    {
        $sm = SubscriptionModel::where('vendor_id', $this->vendorId)
                ->where(function ($query) use ($subscriptionModule) {
                    $query->where('subscription_module', $subscriptionModule)
                      ->orWhere('subscription_module', SubscriptionModule::All);
                })
                        ->first();
        if (! $sm) {
            $sm = new SubscriptionModel();
            $sm->subscription_type = config('finance.default_vendor_subscription.type');
            $sm->amount = config('finance.default_vendor_subscription.charge');
        }

        return $sm;
    }

    public function process($price, $subscriptionModule, $stateId = null, $subscription_included = false)
    {
        $sm = $this->getSubscriptionModel($subscriptionModule);

        return $this->getAmount($price, $sm->subscription_type, $sm->amount, $stateId, $subscription_included);
    }
}
