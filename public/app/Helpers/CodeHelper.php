<?php

namespace  App\Helpers;

use Illuminate\Support\Facades\Facade;

class CodeHelper extends Facade
{
    public function userPassword()
    {
        return \Str::random(10);
    }

    public function userToken()
    {
        return \Str::random(32);
    }

    public static function orderCode($userId = 0)
    {
        return 'DB'.now()->timestamp.$userId;
        //  \Str::random(16);
    }
}
