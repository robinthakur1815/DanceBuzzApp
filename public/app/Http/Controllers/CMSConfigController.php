<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CMSConfigController extends Controller
{
    /*
    Sample Configuration
    "sidebar" => [
       "collections" => [
           ["label" => "Blog", "link" => "/blogs"],
           ["label" => "Events", "link" => "/events"],
       ]
   ]
    */
    public function sidebarConfiguration()
    {
        return config('blcms.sidebar');
    }
}
