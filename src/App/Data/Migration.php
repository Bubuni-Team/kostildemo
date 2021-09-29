<?php
declare(strict_types=1);

namespace App\Data;


use App\Migration\DemoArchive;
use App\Migration\Install;

class Migration
{
    public static function get(): array
    {
        return [
            Install::class,
            DemoArchive::class
        ];
    }
}
