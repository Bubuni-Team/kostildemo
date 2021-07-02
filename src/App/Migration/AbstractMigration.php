<?php
declare(strict_types=1);

namespace App\Migration;


abstract class AbstractMigration
{
    protected $db;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Runs when migration is up.
     */
    public abstract function up(): void;

    /**
     * Runs when migration is down.
     * Generally this will not be used.
     */
    public abstract function down(): void;

    /**
     * Executes a query to database.
     *
     * @param string $query
     * @return \PDOStatement
     */
    public function query(string $query): \PDOStatement
    {
        return $this->db->query($query);
    }
}
