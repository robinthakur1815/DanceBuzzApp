<?php

namespace App;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class Guardian extends Model
{
    protected $connection = 'partner_mysql';

    protected $table = 'guardians';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'email', 'phone1', 'phone2', 'address', 'city', 'zipcode', 'state_id', 'created_by', 'updated_by',
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
     * Get the State for the Gaurdian.
     */
    public function state()
    {
        return $this->setConnection('mysql2')->belongsTo('App\State');
    }

    /**
     * Get the User for the Gaurdian.
     */
    public function user()
    {
        return $this->setConnection('mysql2')->belongsTo(\App\User::class);
    }

    /**
     * Get the Students for the Gaurdian.
     */
    public function students()
    {
        return $this->setConnection('mysql2')->belongsToMany(\App\Student::class, 'student_guardian', 'guardian_id', 'student_id')->withPivot('relationship_id');
    }
}
