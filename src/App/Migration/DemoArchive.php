<?php
declare(strict_types=1);

namespace App\Migration;


class DemoArchive extends SimpleAbstractQueryMigration
{
    protected $upQueries = ["
ALTER TABLE `record`
	ADD COLUMN `compress_algo` VARCHAR(32) NOT NULL DEFAULT 'as_is' AFTER `finished_at`,
	ADD COLUMN `compress_algo_data` BLOB NOT NULL AFTER `compress_algo`;
        ",
        "UPDATE `record` SET `compress_algo_data` = '[]'"
    ];

    protected $downQueries = ["
ALTER TABLE `record`
	DROP COLUMN `compress_algo`,
	DROP COLUMN `compress_algo_data`;
        "
    ];
}
