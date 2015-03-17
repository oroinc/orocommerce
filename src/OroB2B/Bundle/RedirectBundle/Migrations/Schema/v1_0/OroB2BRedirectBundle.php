<?php

namespace OroB2B\Bundle\RedirectBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BRedirectBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroB2BRedirectSlugTable($schema);
    }

    /**
     * Create orob2b_redirect_slug table
     *
     * @param Schema $schema
     */
    protected function createOroB2BRedirectSlugTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_redirect_slug');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('url', 'string', ['length' => 255]);
        $table->addColumn('route_name', 'string', ['length' => 255]);
        $table->addColumn('route_parameters', 'string', ['length' => 255]);
        $table->setPrimaryKey(['id']);
    }
}
