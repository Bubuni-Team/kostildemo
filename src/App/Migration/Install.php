<?php
declare(strict_types=1);

namespace App\Migration;


class Install extends SimpleAbstractQueryMigration
{
    protected $upQueries = ["
CREATE TABLE `record` (
	`record_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	`demo_id` VARCHAR(36) NOT NULL COLLATE 'utf8_general_ci',
	`server_id` VARCHAR(16) NOT NULL DEFAULT '0' COLLATE 'utf8_general_ci',
	`map` VARCHAR(128) NOT NULL COLLATE 'utf8_general_ci',
	`uploaded_at` INT(10) UNSIGNED NOT NULL,
	`started_at` INT(10) UNSIGNED NOT NULL,
	`finished_at` INT(10) UNSIGNED NOT NULL,
	PRIMARY KEY (`record_id`) USING BTREE
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB;
        ", "
CREATE TABLE `record_player` (
	`record_id` INT(11) UNSIGNED NOT NULL,
	`account_id` INT(11) UNSIGNED NOT NULL,
	`username` VARCHAR(128) NOT NULL COLLATE 'utf8_general_ci',
	PRIMARY KEY (`record_id`, `account_id`) USING BTREE,
	CONSTRAINT `FK_record_player_record` FOREIGN KEY (`record_id`) REFERENCES `record` (`record_id`) ON UPDATE RESTRICT ON DELETE RESTRICT
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB;
        ", "CREATE TABLE IF NOT EXISTS `data_registry` (
  `data_key` varchar(32) NOT NULL,
  `data_value` mediumblob NOT NULL,
  PRIMARY KEY (`data_key`),
  UNIQUE KEY `data_registry_data_key_uindex` (`data_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;"];

    protected $downQueries = [
        "DROP TABLE `record_player`;",
        "DROP TABLE `record`;",
        "DROP TABLE `data_registry`;"
    ];
}
