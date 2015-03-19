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
        $this->createOrob2BRedirectSlugTable($schema);
    }

    /**
     * Create orob2b_redirect_slug table
     *
     * @param Schema $schema
     */
    protected function createOrob2BRedirectSlugTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_redirect_slug');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('url', 'string', ['length' => 1024]);
        $table->addColumn('route_name', 'string', ['length' => 255]);
        $table->addColumn('route_parameters', 'array', ['comment' => '(DC2Type:array)']);
        $table->setPrimaryKey(['id']);
    }
}
