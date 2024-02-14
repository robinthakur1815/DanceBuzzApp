<?php

namespace App\Http\Controllers;

use App\Country;
use App\Enums\VendorStatus;
use App\Http\Resources\CMS\Vendor as CMSVendorResource;
use App\Http\Resources\VendorCollection;
use App\Model\Partner\Location;
use App\Jobs\VendorStatusUpdateMail;
use App\Model\Partner\State;
use App\School;
use Dompdf\Dompdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\StoriesExport;
use DB;
use App\User;
use Carbon\Carbon;
use App\Vendor;
use App\VendorCategories;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;


class VendorController extends Controller
{
    public function getAllVendors(Request $request)
    {   
        
        $vendors = Vendor::with('approvedBy','services')->latest();
        if ($request->vendor_type) {
            $vendors = $vendors->where('vendor_type', $request->vendor_type);
        }
        if ($request->search) {
            $vendors = $vendors->where(function ($query) use ($request) {
                $query->where('name', 'like', "%{$request->search}%")
                    ->orWhere('contact_first_name', 'like', "%{$request->search}%")
                    ->orWhere('contact_email', 'like', "%{$request->search}%")
                    ->orWhere('contact_phone1', 'like', "%{$request->search}%");
            });
        }
        if ($request->status) {
            $vendors = $vendors->where('status', $request->status);
        }
        if($request->state){
            $vendors = $vendors->where('state_id', $request->state);
        }
        if($request->city){
            $vendors = $vendors->where('city', $request->city);
        }
        if($request->country){
            $vendors = $vendors->where('country_id', $request->country);
        }
        if($request->start_date){
            $vendors = $vendors->whereBetween('created_at',  [$request->start_date, Carbon::parse($request->end_date)->addDays(1)]);
        }
        if($request->service){
            $serviceId = $request->service;
            $vendors = $vendors->whereHas('services',  function ($query) use ($serviceId){
                $query->where('service_id', $serviceId);
            });
        }

        
        if ($request->isTrashed) {
            $vendors = $vendors->onlyTrashed();
        }
        if ($request->maxRows) {
            $vendors = $vendors->paginate($request->maxRows);
        } else {
            $vendors = $vendors->get();
        }
        foreach($vendors as $vendor){
            $url = null;
            $user = User::find($vendor->created_by);
            if($vendor->state_id){
                $state = State::find($vendor->state_id);
                $vendor['state_name'] = $state->name;
            }
            
            if($user){
                $url = $user->getPartnerAvatarAttribute();
                $vendor['avatar'] = $url;
            }
            
        
        }    

        return new VendorCollection($vendors);
    }

    public function downloadCSV(Request $request){

        $search = $request->search ? $request->search : null;
        $vendor_type = $request->vendor_type ? $request->vendor_type : null;
        $serviceId = $request->service ? $request->service : null;
        $city = $request->city ? $request->city : null;
        $country = $request->country ? $request->country : null;
        $state = $request->state ? $request->state : null;
        $start_date = $request->start_date ? $request->start_date : null;
        $end_date = $request->end_date ? $request->end_date : null;
        $status = $request->status ? $request->status : null;

        $vendors = Vendor::with('approvedBy','services')->latest();
        if ($request->vendor_type) {
            $vendors = $vendors->where('vendor_type', $request->vendor_type);
        }
        if ($request->search) {
            $vendors = $vendors->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('contact_first_name', 'like', "%{$search}%")
                    ->orWhere('contact_email', 'like', "%{$search}%")
                    ->orWhere('contact_phone1', 'like', "%{$search}%");
            });
        }
        if ($status) {
            $vendors = $vendors->where('status', $status);
        }
        if($state){
            $vendors = $vendors->where('state_id', $state);
        }
        if($request->city){
            $vendors = $vendors->where('city', $request->city);
        }
        if($request->country){
            $vendors = $vendors->where('country_id', $request->country);
        }
        if($request->start_date){
            $vendors = $vendors->whereBetween('created_at',  [$request->start_date, Carbon::parse($request->end_date)->addDays(1)]);
        }
        if($request->service){
            $serviceId = $request->service;
            $vendors = $vendors->whereHas('services',  function ($query) use ($serviceId){
                $query->where('service_id', $serviceId);
            });
        }
        $vendors = $vendors->get();

        $headers = [
            'Name',
            'Contact_name',
            'Email',
            'Phone',
            'Country',
            'State',
            'City',
            'status',
            'Created_at',
            'Services',
        ];
        if($request->vendor_type == 2){
            $headers = [
                'Name',
                'Contact_name',
                'Email',
                'Phone',
                'Country',
                'State',
                'City',
                'status',
                'Created_at',
            ];
        }
        $bodies = [];
        foreach($vendors as $data){
            $name = $data->name;
            $contact_name =  $data->contact_first_name;
            $email = $data->contact_email;
            $phone = $data->contact_phone;
            $country = DB::connection('partner_mysql')->table('countries')->where('id', $data->country_id)->first();
            $country = $country ? $country->name : 'N/A';
            $state = State::find($data->state_id);
            $state = $state ? $state->name : 'N/A';
            $city = $data->city ? $data->city: 'N/A';
            $createdAt = $data->created_at->format('d/m/Y h:i A');
            $services = $data->services;
            $service_name = [];
            $status = VendorStatus::getKey($data->status);
            foreach($services as $service){
                array_push($service_name, $service->name);
            }
            $serviceName = implode(",",$service_name); 
            
            $body = [
                $name,
                $contact_name,
                $email,
                $phone,
                $country,
                $state,
                $city,
                $status,
                $createdAt,
                $serviceName
            ];
            if($request->vendor_type == 2){
                $body = [
                    $name,
                    $contact_name,
                    $email,
                    $phone,
                    $country,
                    $state,
                    $city,
                    $status,
                    $createdAt,
                ];
            }
            array_push($bodies, $body);
        }
        

        return Excel::download(new StoriesExport($headers, $bodies), 'partners.csv');

    }

    public function getAllCategories()
    {
        $categories = VendorCategories::get();

        return $categories;
    }

    public function getAllSchools()
    {
        $schools = School::all('id', 'name');

        return $schools;
    }

    public function getVendorListing()
    {
        $vendors = Vendor::all('id', 'name');

        return $vendors;
    }

    /**
     * Update vendor status.
     */
    public function updateVendorStatus(Request $request)
    {
        $user = auth()->user();
        $status = $request->status;
        $vendors = Vendor::whereIn('id', $request->vendorIds)->get();
        foreach ($vendors as $vendor) {
            $data = [
                'status' => $request->status,
                'approved_at' => now(),
                'rejection_reason' => $request->reason,
                'approved_by' => json_encode($request->auth_user),
            ];
            $vendor->update($data);
            $vendor->refresh();
            $user = User::where('id','=',$vendor->created_by)->first();
            if($request->status == VendorStatus::Active){
                    $user->is_active = true;
            }else{
                $user->is_active = false;
            }
            $user->save();

             try {
                 VendorStatusUpdateMail::dispatch($vendor);
            } catch (Exception $e) {
                 info($e);
            }
        }

        return response(['status' => true, 'message' => 'Successfully updated'], 200);
    }

    public function getSingleVendor($id)
    {
        $vendor = Vendor::withTrashed()->with(['documents', 'staffVendors.user', 'locations', 'fees', 'discounts', 'certificates', 'state', 'services', 'active_services'])->find($id);

        if (! $vendor) {
            return response(['errors' =>  ['vendor not Found'], 'status' => false, 'message' => ''], 422);
        }

        // return $vendor ;

        return  new CMSVendorResource($vendor);
    }

    public function vendorLocations($id)
    {
        $locations = Location::where('vendor_id', $id)->get();

        return $locations;
    }
}

