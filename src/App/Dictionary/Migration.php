<?php


namespace App\Dictionary;


use App\Migration\Install;

class Migration
{
    public static function get(): array
    {
        return [
            Install::class
        ];
    }
}
