<?php

namespace App\Model\Partner;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    protected $connection = 'partner_mysql';

    protected $table = 'certificates';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'registration_code', 'vendor_id', 'student_id', 'certificatetemplate_id', 'issued_at',
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
     * Get the Vendor for the Certificate.
     */
    public function vendor()
    {
        return $this->belongsTo(\App\Vendor::class);
    }

    /**
     * Get the Student for the Certificate.
     */
    public function student()
    {
        return $this->belongsTo(\App\Student::class);
    }
}
