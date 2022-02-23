<?php
declare(strict_types=1);

namespace App\Data;


use App\Migration\DemoArchive;
use App\Migration\FixPlayerFk;
use App\Migration\FixPlayerNoSteamIdentifiers;
use App\Migration\Install;

class Migration
{
    public static function get(): array
    {
        return [
            Install::class,
            DemoArchive::class,
            FixPlayerNoSteamIdentifiers::class,
            FixPlayerFk::class
        ];
    }
}
