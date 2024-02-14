<?php

namespace App\Http\Resources\CMS;

use App\Enums\RoleType;
use App\Http\Resources\Vendor\VendorDocumentCollection;
use App\Http\Resources\Vendor\VendorServiceCollection;
use App\Http\Resources\Vendor\VendorServiceStaffCollection;
use App\Http\Resources\Vendor\VendorUserCollection;
use App\Role;
use Illuminate\Http\Resources\Json\JsonResource;

// use App\Http\Resources\User as UserResource;

class Vendor extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $services = $this->active_services;
        $services = $services->map(function ($service) {
            $service['isSelected'] = $service->pivot->isActive ? true : false;

            return $service;
        });

        $data = [
            'id'                    => $this->id,
            'created_by'            => $this->created_by,
            'name'                  => $this->name,
            'description'           => $this->description,
            'contact_name'          => $this->contact_first_name.' '.$this->contact_last_name,
            'contact_email'         => $this->contact_email,
            'contact_phone'         => $this->contact_phone1,
            'documents'             => ($this->documents && count($this->documents) > 0) ? new VendorDocumentCollection($this->documents) : '',
            'services'              => ($services && count($services) > 0) ? new VendorServiceCollection($services) : '',
            'vendor_staff'          => ($this->staffVendors && count($this->staffVendors) > 0) ? $this->vendorStaff($this->staffVendors) : '',
            'locations'             => $this->multipleLocations($this->locations),
            'created_at'            => $this->created_at,
            'approved_at'           => $this->approved_at,
            'address'               => $this->address.' '.$this->city,
            'address1'              => $this->address,
            'city'                  => $this->city,
            'state'                 => $this->state ? $this->state->name : '',
            'zipcode'               => $this->zipcode,
            'status'                => $this->status,
            'approved_by'           => $this->getApproverName($this->approved_by),
            'longitude'             => $this->longitude,
            'latitude'              => $this->latitude,
            'rejection_reason'      => $this->rejection_reason,
            'verification_renew_at' => $this->verification_renew_at,
            'created_at'            => $this->created_at,
        ];

        return $data;
    }

    public function multipleLocations($datas)
    {
        $locations = [];
        foreach ($datas as $data) {
            $loc['address'] = $data->address;
            $loc['zipcode'] = $data->zipcode;
            $loc['city'] = $data->city;
            $loc['state'] = $data->state->name;
            $loc['isActive'] = $data->isActive ? 1 : 0;
            $loc['isVerified'] = $data->isVerified ? 1 : 0;
            $loc['id'] = $data->id;
            $loc['contact_email'] = $data->contact_email;
            $loc['contact_phone1'] = $data->contact_phone1;
            $loc['contact_phone2'] = $data->contact_phone2;

            $locations[] = $loc;
        }

        return $locations;
    }

    public function vendorStaff($vendorStaff)
    {
        $users = [];
        foreach ($vendorStaff as $staff) {
            $staffUser = $staff->user;
            $user['id'] = $staff->id;
            $user['user_id'] = $staff->user_id;
            $user['name'] = $staffUser->name;
            $user['email'] = $staffUser->email;
            $user['role_name'] = ($staff->role_id == RoleType::Vendor) ? 'Admin' : 'Teacher';
            $user['role_id'] = $staff->role_id;
            $user['is_active'] = $staffUser->is_active;
            $user['phone'] = $staffUser->phone;
            $user['username'] = $staffUser->username;
            $user['created_at'] = $staff->created_at;
            $users[] = $user;
        }

        return $users;
    }

    public function getApproverName($data)
    {
        if ($data == null) {
            return '';
        }
        $user = json_decode($data);
        if ($user && isset($user->name) && $user->name) {
            return $user->name;
        }

        return '';
    }
}
