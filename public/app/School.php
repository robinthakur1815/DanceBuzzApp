<?php

namespace App;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    protected $connection = 'partner_mysql';

    protected $table = 'schools';

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
    //public function state()
    //{
    //    return $this->setConnection('partner_mysqlmysql2')->belongsTo('App\State');
    //}
 

    public function statename()
    {
        return $this->setConnection('mysql2')->belongsTo(App\State::class);
    }

    public function state()
    {
        return $this->setConnection('partner_mysql')->belongsTo(\App\Model\Partner\State::class);
    }
}
