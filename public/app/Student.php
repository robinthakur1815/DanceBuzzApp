<?php

namespace App;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $connection = 'partner_mysql';

    protected $table = 'students';

    public function user()
    {
        return $this->setConnection('mysql2')->belongsTo(\App\User::class, 'user_id');
    }

    public function students()
    {
        return $this->setConnection('mysql2')->belongsTo(\App\Student::class, 'user_id');
    }

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
     * Get the student_gaurdians for the Student.
     */
    public function guardians()
    {
        return $this->setConnection('partner_mysql')->belongsToMany(\App\Guardian::class, 'student_guardian', 'student_id', 'guardian_id')->orderBy('id', 'ASC')->withPivot('relationship_id');
    }

    /**
     * Get the registrations for the Student.
     */
    public function registrations()
    {
        return $this->setConnection('partner_mysql')->hasMany(\App\Model\Partner\StudentRegistration::class, 'student_id')->orderBy('id', 'ASC');
    }

    /**
     * Get the student_registrations for the Student.
     */
    public function studentRegistrations()
    {
        return $this->setConnection('partner_mysql')->hasMany(\App\Model\Partner\StudentRegistration::class);
    }

    /**
     * Get the student_classes for the Student.
     */
    public function studentClasses()
    {
        return $this->setConnection('partner_mysql')->hasMany('App\student_class');
    }

    /**
     * Get the Payments for the Student.
     */
    public function payments()
    {
        return $this->setConnection('partner_mysql')->hasMany('App\Payment');
    }

    /**
     * Get the student_attendances for the Student.
     */
    public function studentAttendances()
    {
        return $this->setConnection('partner_mysql')->hasMany('App\StudentAttendance', 'student_id');
    }

    /**
     * Get the Certificates for the Student.
     */
    public function certificates()
    {
        return $this->setConnection('partner_mysql')->hasMany('App\Certificate');
    }

    /**
     * Get the State for the Student.
     */
    public function state()
    {
        return $this->setConnection('partner_mysqlmysql2')->belongsTo('App\State');
    }

    /**
     * Get the State for the Student.
     */
    public function school()
    {
        return $this->setConnection('partner_mysql')->belongsTo(\App\School::class);
    }
   

    
    // /**
    //  * Get the Gaurdians for the Student.
    //  */
    // public function gaurdians()
    // {
    //     return $this->belongsToMany('App\Gaurdian', 'student_guardian');
    // }
}
