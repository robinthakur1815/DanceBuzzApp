<?php

namespace App\Model\Partner;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    protected $connection = 'partner_mysql';

    protected $table = 'states';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        //
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
     * Get the Vendors for the State.
     */
    public function vendors()
    {
        return $this->hasMany(\App\Vendor::class);
    }

    /**
     * Get the Students for the State.
     */
    public function students()
    {
        return $this->hasMany(\App\Student::class);
    }

    /**
     * Get the Locations for the State.
     */
    public function locations()
    {
        return $this->hasMany(\App\Model\Partner\Location::class);
    }

    /**
     * Get the Gaurdians for the State.
     */
    public function gaurdians()
    {
        return $this->hasMany('App\Model\Partner\Gaurdian');
    }
}
