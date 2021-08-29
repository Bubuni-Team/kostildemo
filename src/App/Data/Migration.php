<?php


namespace App\Data;


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
