<?php

namespace  App\Helpers;

use App\Collection as AppCollection;
use App\Enums\CollectionType;
use Illuminate\Support\Facades\Facade;
use App\Model\Partner\State as PartnerState;


class WebHelper extends Facade
{
    public function collection_type($type)
    {
        $data = AppCollection::where('collection_type', CollectionType::getValue($type));

        return $data;
    }


    public static function state($request, $stateIdOnly = false, $optional = false)
    {
        $isMobile = $request->isMobile;
        if (!$isMobile) {
            $isMobile = $request->is_mobile;
        }
        $stateId = $request->state_id;
        $stateData = null;
        if ($isMobile) {
            // $stateName = $stateId;
            $stateName = strtolower($stateId);
            $stateData = PartnerState::where('name', 'like', "%${stateName}%")
                                ->where(function($q) use($stateName) {
                                    $q->where('code', 'like', "%${stateName}%")
                                    ->orWhere('other_name->name', 'like', "%${stateName}%");
                                })->first();

            if (!$stateData and $optional) {
                $stateData = PartnerState::first();
            }
           
        }else{
            $stateData = PartnerState::find($stateId);
        }

        if ($stateIdOnly) {
            return $stateData ? $stateData->id : null;
        }

        return $stateData;
    }
}
