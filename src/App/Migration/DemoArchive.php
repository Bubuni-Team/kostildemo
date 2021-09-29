<?php
declare(strict_types=1);

namespace App\Migration;


class DemoArchive extends SimpleAbstractQueryMigration
{
    protected $upQueries = ["
ALTER TABLE `record`
	ADD COLUMN `algo` VARCHAR(32) NOT NULL DEFAULT 'as_is' AFTER `finished_at`,
	ADD COLUMN `algo_data` BLOB NOT NULL AFTER `algo`;
        ",
        "UPDATE `record` SET `algo_data` = '[]'"
    ];

    protected $downQueries = ["
ALTER TABLE `record`
	DROP COLUMN `algo`,
	DROP COLUMN `algo_data`;
        "
    ];
}
