<?php
declare(strict_types=1);

namespace App\Migration;


class FixPlayerNoSteamIdentifiers extends SimpleAbstractQueryMigration
{
    protected $upQueries = ["
        ALTER TABLE `record_player`
            CHANGE COLUMN `account_id` `account_id` INT(11) NOT NULL AFTER `record_id`;
    "];

    protected $downQueries = ["
        ALTER TABLE `record_player`
            CHANGE COLUMN `account_id` `account_id` INT(11) UNSIGNED NOT NULL AFTER `record_id`;
    "];
}
