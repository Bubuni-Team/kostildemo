<?php
/**
 * Created by PhpStorm.
 * User: Andriy
 * Date: 05.07.2021
 * Time: 21:13
 * Made with <3 by West from Bubuni Team
 */

namespace App\Util;


use App;

class Demo
{
    public static function getChunkDirByDemoId(string $demoId): string
    {
        return sprintf('%s/data/chunks/%s', App::$dir, $demoId);
    }

    public static function getDemoFileNameByDemoId(string $demoId): string
    {
        return sprintf('%s/data/demos/%s.dem', App::$dir, $demoId);
    }
}