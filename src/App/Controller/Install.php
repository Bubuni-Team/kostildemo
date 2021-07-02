<?php
/** @noinspection PhpUnused */
declare(strict_types=1);

namespace App\Controller;


use App\Dictionary\Migration;
use App\Migration\AbstractMigration;

class Install extends AbstractController
{
    public function preAction(): void
    {
        if ($this->isInstalled())
        {
            $this->assertIsAdmin();
        }
    }

    public function actionIndex(): string
    {
        $dbError = '';
        $isInstalled = $this->isInstalled($dbError);

        return $this->template('install/index', [
            'isInstalled' => $isInstalled,

            'dbError' => $dbError,
            'serverErrorExplain' => $this->verifyServerConfigurationFill(),

            'secondaryTitle' => 'Install'
        ]);
    }

    public function actionMigrate(): string
    {
        if (!$this->isInstalled())
        {
            throw $this->exception('System isn\'t installed', 400);
        }

        if ($this->runMigrations())
        {
            return 'OK';
        }

        return 'FALSE';
    }

    public function isInstalled(&$dbError = ''): bool
    {
        $isDbError = true;
        try
        {
            $db = $this->db();
            $isDbError = false;

            $db->query('SELECT * FROM `migration`');
            return true;
        }
        catch (\PDOException $e)
        {
            if ($isDbError)
            {
                $dbError = sprintf('[%d] %s', $e->getCode(), $e->getMessage());
            }
            return false;
        }
    }

    public function runMigrations(): bool
    {
        $db = $this->db();
        $migrationClasses = Migration::get();

        $ranMigrations = [];
        try {
            $ranMigrationsStatement = $db->query('SELECT `name` FROM `migration`');
            while ($col = $ranMigrationsStatement->fetchColumn(0)) {
                $ranMigrations[] = $col;
            }
        } catch (\PDOException $e) {
            $db->query("
                CREATE TABLE `migration` (
                    `name` VARCHAR(128) NOT NULL COLLATE 'utf8_general_ci',
                    `runned_at` INT(10) UNSIGNED NOT NULL,
                    PRIMARY KEY (`name`) USING BTREE
                )
                COLLATE='utf8_general_ci'
                ENGINE=InnoDB;
            ");
        }

        $migrationsForRun = array_diff($migrationClasses, $ranMigrations);
        if (empty($migrationsForRun))
        {
            return true;
        }

        $db->beginTransaction();
        $insertMigrationStmt = $db->prepare('INSERT INTO `migration` VALUES(?, ?)');
        foreach ($migrationsForRun as $migration)
        {
            /** @var AbstractMigration $migrationHandler */
            $migrationHandler = new $migration($db);

            try
            {
                $db->beginTransaction();
                $migrationHandler->up();
                $insertMigrationStmt->execute([$migration, time()]);
                $db->commit();
            }
            catch (\PDOException $e)
            {
                while ($db->inTransaction()) $db->rollBack();
                return false;
            }
        }
        $db->commit();
        return true;
    }
}
