<?php

namespace Oro\Bundle\RedirectBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroRedirectBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_2';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroRedirectSlugTable($schema);
        $this->createOroRedirectTable($schema);
    }

    /**
     * Create oro_redirect_slug table
     *
     * @param Schema $schema
     */
    protected function createOroRedirectSlugTable(Schema $schema)
    {
        $table = $schema->createTable('oro_redirect_slug');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('url', 'string', ['length' => 1024]);
        $table->addColumn('route_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('route_parameters', 'array', ['comment' => '(DC2Type:array)']);
        $table->setPrimaryKey(['id']);
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
