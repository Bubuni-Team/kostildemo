<?php
declare(strict_types=1);

namespace App\Migration;


abstract class SimpleAbstractQueryMigration extends AbstractMigration
{
    /** @var array */
    protected $upQueries = [];

    /** @var array */
    protected $downQueries = [];

    public function up(): void
    {
        foreach ($this->upQueries as $query)
        {
            $this->query($query);
        }
    }

    public function down(): void
    {
        foreach ($this->downQueries as $query)
        {
            $this->query($query);
        }
    }
}
