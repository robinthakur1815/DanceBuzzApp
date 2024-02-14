<?php

namespace App\Adapters\Subscription;

use App\Enums\SubscriptionType;

class SubscriptionAdapterBase
{
    protected function getAmount($baseAmount, $subscriptionType, $charges, $stateId = null, $subscription_included = false)
    {
        if (round($baseAmount) <= 0.00) {
            $taxesData = [];
            $taxes = $this->getTaxes($stateId);
            foreach ($taxes as $tax) {
                $tax['tax_amount'] = 0;
                $taxesData[] = $tax;
            }

            return ['total_amount' => 0.0, 'amount' => 0.0,  'taxes' =>  $taxes, 'taxesData'=> $taxesData];
        } elseif (round($baseAmount) > config('finance.max_base_amount')) {
            $maxAmount = config('finance.max_base_amount');
            throw new \Exception("Amount exceeds the limit - INR {$maxAmount}");
        }

        $projectedCast = $baseAmount;
        switch ($subscriptionType) {
            case SubscriptionType::PercentageCharge:
                $projectedCast += $baseAmount * $charges / 100;
                break;
            case SubscriptionType::FlatCharge:
                    $projectedCast += $charges;
                    break;
            case SubscriptionType::NoCharge:
                break;
        }
        // $subscription_included = request('subscription_included');
        //After the payment gateway commission inclusion
        $projectedCast += $projectedCast * config('finance.payment_gateway.charge') / 100;
        if ($subscription_included) {   // check if already subscription and processing charges added
            $projectedCast = $baseAmount;
        }
        $totalCost = $projectedCast = round($projectedCast);

        // GST Handling
        $taxes = $this->getTaxes($stateId);
        $totalTax = 0;
        $taxesData = [];
        foreach ($taxes as $tax) {
            $taxAmount = $totalCost * $tax['value'] / 100;
            // $totalTax += $totalCost*$tax["value"]/100;
            $totalTax += $taxAmount;
            $tax['tax_amount'] = round($taxAmount);
            $taxesData[] = $tax;
        }
        $totalCost += $totalTax;

        return [
           'total_amount' => round($totalCost),
           'amount'       => round($projectedCast),
           'taxes'        => $taxes,
           'taxesData'    => $taxesData,
        ];
    }

    protected function getTaxes($stateId = null)
    {
        // Get taxes slab
        // In future, it could be external API
        $taxes = config('finance.online_taxes');

        return array_filter($taxes, function ($tax) {
            return $tax['value'] != 0;
        });
    }
}
