<?php

namespace App\Model\Partner;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentRegistration extends Model
{
    use SoftDeletes;
    protected $table = 'student_registration';

    protected $connection = 'partner_mysql';

    protected $fillable = [
        'registration_code', 'student_id', 'user_id', 'location_id', 'vendor_id',
        'vendorclass_id', 'fee_id', 'discount_id', 'coupon_id', 'remarks',
        'start_date', 'end_date', 'created_by', 'updated_by',
    ];

    protected $dates = [
        'start_date', 'end_date',
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

    public function paymentScheduls()
    {
        return $this->hasMany('App\PaymentSchedule', 'studentregistration_id');
    }

    public function student()
    {
        return $this->belongsTo(\App\Student::class, 'student_id');
    }

    public function guardian()
    {
        return $this->belongsTo(\App\Student::class, 'student_id');
    }
}
