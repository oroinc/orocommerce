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
        $this->createOroSlugScopeTable($schema);

        /** Foreign keys generation **/
        $this->addOroSlugScopeForeignKeys($schema);
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
        $table->addColumn('url', 'string', ['length' => 255]);
        $table->addColumn('route_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('route_parameters', 'array', ['comment' => '(DC2Type:array)']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['url'], 'oro_redirect_slug_url', []);
    }

    /**
     * Create oro_slug_scope table
     *
     * @param Schema $schema
     */
    protected function createOroSlugScopeTable(Schema $schema)
    {
        $table = $schema->createTable('oro_slug_scope');
        $table->addColumn('slug_id', 'integer', []);
        $table->addColumn('scope_id', 'integer', []);
        $table->setPrimaryKey(['slug_id', 'scope_id']);
    }

    /**
     * Add oro_slug_scope foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroSlugScopeForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_slug_scope');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_redirect_slug'),
            ['slug_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_scope'),
            ['scope_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
