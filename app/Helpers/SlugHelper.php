<?php

namespace  App\Helpers;

use Illuminate\Support\Facades\Facade;

class SlugHelper extends Facade
{
    public static function slugify($text)
    {
        // replace non letter or digits by -
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);

        // transliterate
        // $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        // trim
        $text = trim($text, '-');

        // remove duplicate -
        $text = preg_replace('~-+~', '-', $text);

        // lowercase
        $text = strtolower($text);

        if (empty($text)) {
            return 'n-a';
        }

        return $text;
    }

    public static function getSlugAndNameFeed($title, $feed = null)
    {
        $title = preg_replace('!\s+!', ' ', $title);
        if ($feed  and strtolower($feed->title) != strtolower($title)) {
            $slug = self::slugify($feed->title) . '-' . now()->timestamp;
        } else {
            if ($feed) {
                $slug = $feed->slug;
            } else {
                $slug = self::slugify($title) . '-' . now()->timestamp;
            }
        }

        $data = new \stdClass();
        $data->title = $title;
        $data->slug = $slug;

        return $data;
    }

    /**
     * Function will clip a string with the length specified
     * It also respects a word boundry.
     */
    public static function clipString($str, $length)
    {
        $str = trim($str);
        $pos = strpos($str, ' ', $length);

        return substr($str, 0, $pos);
    }
}
