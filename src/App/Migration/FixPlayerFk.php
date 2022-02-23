<?php

namespace App\Migration;

class FixPlayerFk extends SimpleAbstractQueryMigration
{
    protected $upQueries = ["
        ALTER TABLE `record_player`
	        DROP FOREIGN KEY `FK_record_player_record`;
", "
        ALTER TABLE `record_player`
	        ADD CONSTRAINT `FK_record_player_record`
	            FOREIGN KEY (`record_id`)
	                REFERENCES `record` (`record_id`)
	            ON UPDATE CASCADE
	            ON DELETE RESTRICT;
    "];

    public function preDown(): void
    {
        throw new \RuntimeException("This migration is cannot be rollback");
    }
}
