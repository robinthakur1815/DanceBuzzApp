<?php

namespace App\Http\Resources;

use App\Enums\RoleType;
use App\Enums\UserRole;
use App\Model\Student;
use App\User as DataModel;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class User extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {

        // return parent::toArray($request);

        $url = null;
        // $this->load('avatarMediable');
        if (! $this->avatarMediable) {
            $tempImage = '/images/placeholder.jpg';
            if (isset($this->no_temp_profile) && $this->no_temp_profile) {
                $tempImage = '';
            }
            $url = $request->isMobile ? '' : $tempImage;
        } else {
            $url = Storage::url($this->avatarMediable->media->url);
        }
        unset($this->avatarMediable);
        $this['profile_url'] = $url;
        $authUser = DataModel::find($this->id);
        if($authUser){
            $authUser->load('avatar');
        $authUser->load('profile');
        }
       
        $userAvatar = null;
        if ($authUser && $authUser->partnerAvatar) {
            $userAvatar = $authUser->partnerAvatar;
        }

        if ($request->isMobile) {
            if ($this->partnerAvatar) {
                $this['profile_url'] = $this->partnerAvatar;
            }

            return [
                'id' => $this->id,
                'name' => $this->name,
                'profile_url' => $this->profile_url,
                'created_at' =>  $this->created_at,
            ];
       //} else {
       //    $guardian_name = "";
       //    if($this->role_id == RoleType::Student){
       //        $student = Student::with('guardians')->where('id', $this->link)->first();
       //        $guardians = $student->guardians;
       //        $guardian_uid= $guardians['0']['user_id'];
       //        $user = DataModel::where('id',$guardian_uid)->first();
       //        $guardian_name= $user->name;

           }
            return [
                'id' => $this->id,
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone,
                'avatar' => $userAvatar,
                'is_active' => $this->is_active,
                'profile_url' => $this->profile_url,
                'role_id' => $this->role_id,
                'role_name' =>  $this->role_id ? UserRole::getKey($this->role_id) : '',
                'created_at' =>  $this->created_at,
                'user_type' => $this->role_id ? RoleType::getKey($this->role_id) : '',
                'link' => $this->link,
              //  'guardian' => $guardian_name,
            ];
        }
    }

