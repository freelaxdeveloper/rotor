<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class CreateLoadsTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change()
    {
        if (! $this->hasTable('loads')) {
            $table = $this->table('loads', ['collation' => env('DB_COLLATION')]);
            $table->addColumn('down', 'integer', ['limit' => MysqlAdapter::INT_MEDIUM, 'signed' => false])
                ->addColumn('ip', 'string', ['limit' => 15])
                ->addColumn('time', 'integer')
                ->create();
        }
    }
}
