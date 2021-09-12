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
        $this->preUp();

        foreach ($this->upQueries as $query)
        {
            $this->query($query);
        }

        $this->postUp();
    }

    public function preUp(): void
    {
    }

    public function postUp(): void
    {
    }

    public function down(): void
    {
        $this->preDown();

        foreach ($this->downQueries as $query)
        {
            $this->query($query);
        }

        $this->postDown();
    }

    public function preDown(): void
    {
    }

    public function postDown(): void
    {
    }
}
