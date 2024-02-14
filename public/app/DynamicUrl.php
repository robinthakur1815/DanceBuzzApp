<?php


namespace App;


use Illuminate\Database\Eloquent\Model;

class DynamicUrl extends Model
{
    protected $table = 'dynamicurls';
    protected $connection = 'partner_mysql';

    public function dynamicurlable()
    {
        return $this->morphTo();
    }
}

