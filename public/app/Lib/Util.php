<?php

namespace  App\Lib;

use App\Enums\CollectionType;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Str;

final class Util extends Facade
{
    public static function getCode($name, $phone)
    {
        $prefix = self::getCodePrefix($name);

        return [$prefix.self::sanitizePhone($phone), $prefix];
    }

    public static function getCodePrefix($name, $length = 4)
    {
        if (strlen($name) < $length) {
            $name .= $name;
        }

        return strtoupper(substr($name, 0, 4));
    }

    public static function sanitizePhone($phone)
    {
        $str = preg_replace('/[^\d]/', '', $phone);
        $str = preg_replace('/^0+/', '', $str);

        return $str;
    }

    public static function collectionTypeLookup($type)
    {
        if(blank($type)) return '';
        return optional(CollectionType::coerce($type))->description;
    }

    public static function fileSearchByNameInDirectory($dirName, $name)
    {
        if (!is_dir($dirName)) return null;

        $files = scandir($dirName);
        $pattern = '/' . $name . '\.(jpg|jpeg|png)$/';
        foreach ($files as $file) {
            $found = preg_match($pattern, $file);
            if ($found)
                return "{$dirName}/{$file}";
        }
        return null;
    }
}
