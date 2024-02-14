<?php

namespace App\Model\Partner;

use DateTimeInterface;
use Illuminate\Database\Eloquent\SoftDeletes;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
// use Laravel\Passport\HasApiTokens;
// use League\OAuth2\Server\Exception\OAuthServerException;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable, SoftDeletes;

    protected $connection = 'partner_mysql';

    protected $table = 'users';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'username', 'phone', 'email_verified_at', 'password',
        'otp', 'role_id', 'is_active', 'remember_token', 'app_type',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'otp',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Prepare a date for array / JSON serialization.
     *
     * @param  \DateTimeInterface  $date
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    /**
     * Get the user for the avatar.
     */
    public function avatar()
    {
        return $this->setConnection('partner_mysql')->morphOne(\App\Media::class, 'model');
    }

    public function role()
    {
        // return \App\Role::where('id', 1)->first();
        return $this->setConnection('partner_mysql')->belongsTo(\App\Role::class);
    }

    /**
     * Get the staff_vendors for the User.
     */
    public function staffVendors()
    {
        return $this->setConnection('partner_mysql')->belongsToMany(\App\Vendor::class, 'staff_vendor', 'user_id', 'vendor_id')->withPivot('role_id');

        return $this->setConnection('partner_mysql')->belongsToMany(\App\Vendor::class)
            ->using('App\StaffVendor', 'user_id', 'vendor_id')
            ->withPivot([
                'role_id',
            ]);
        // return $this->hasMany('App\StaffVendor', 'user_id');
    }

    /**
     * The skills that belong to the user.
     */
    public function skills()
    {
        return $this->setConnection('partner_mysql')->belongsToMany('App\Skill', 'skill_user', 'user_id', 'skill_id')
            ->withTimestamps()
            ->withPivot('isActive');
    }

    /**
     * Get the Gaurdian for the User.
     */
    public function guardian()
    {
        return $this->setConnection('partner_mysql')->hasOne(\App\Guardian::class);
    }

    /**
     * Get the Student for the User.
     */
    public function student()
    {
        return $this->setConnection('partner_mysql')->hasOne(\App\Student::class);
    }

    /**
     * Get the student_gaurdians for the User.
     */
    public function studentGaurdians()
    {
        return $this->setConnection('partner_mysql')->hasMany('App\student_gaurdian');
    }

    /**
     * Get the class_teachers for the User.
     */
    public function classTeachers()
    {
        return $this->setConnection('partner_mysql')->hasMany('App\class_teacher');
    }

    /**
     * Get the class_teachers for the User.
     */
    public function classes()
    {
        return $this->setConnection('partner_mysql')->belongsToMany('App\VendorClass', 'class_teacher', 'user_id', 'vendorclass_id')
            ->withTimestamps()
            ->withPivot('isActive');
    }

    /**
     * Get the user_notificaitons for the User.
     */
    public function userNotificaitons()
    {
        return $this->setConnection('partner_mysql')->hasMany('App\user_notificaiton');
    }

    /**
     * Get the Locations for the Vendor.
     */
    public function locations()
    {
        return $this->setConnection('partner_mysql')->hasMany('App\Location', 'user_id');
    }

    /**
     * Get the user full name.
     */
    public function getFullNameAttribute()
    {
        $name = $this->name;
        if ($this->last_name) {
            $name = $name.' '.$this->last_name;
        }

        return $name;
    }

    /**
     * Get User Profile.
     */
    public function profile()
    {
        return $this->setConnection('partner_mysql')->hasOne(\App\UserProfile::class);
    }

    /**
     * validate vendor Id.
     */
    public function isValidVendorId($vendorId)
    {
        $this->load('staffVendors');
        $vendors = $this->staffVendors;
        $vendorId = $vendors->where('id', $vendorId)->first();

        return $vendorId ? true : false;
    }
}
