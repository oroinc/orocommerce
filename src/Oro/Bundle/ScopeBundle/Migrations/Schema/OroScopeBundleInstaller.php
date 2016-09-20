<?php

namespace Oro\Bundle\ScopeBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroScopeBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1.0';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createScopeTable($schema);
    }

    /**
     * @param Schema $schema
     */
    protected function createScopeTable(Schema $schema)
    {
        $table = $schema->createTable('oro_scope');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
    }
}
