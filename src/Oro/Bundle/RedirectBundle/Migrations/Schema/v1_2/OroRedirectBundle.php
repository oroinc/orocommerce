<?php

namespace Oro\Bundle\RedirectBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroRedirectBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroRedirectTable($schema);
    }

    /**
     * Create orob2b_redirect table
     *
     * @param Schema $schema
     */
    protected function createOroRedirectTable(Schema $schema)
    {
        $table = $schema->createTable('oro_redirect');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('from', 'string', ['notnull' => true, 'length' => 1024]);
        $table->addColumn('to', 'string', ['notnull' => true, 'length' => 1024]);
        $table->addColumn('type', 'integer', ['notnull' => true, 'comment' => '(301 or 302)']);
        $table->setPrimaryKey(['id']);
    }
}
