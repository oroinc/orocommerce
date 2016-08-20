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
        return 'v1_0';
    }

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
        $table->addColumn('route_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('route_parameters', 'array', ['comment' => '(DC2Type:array)']);
        $table->setPrimaryKey(['id']);
    }
}
