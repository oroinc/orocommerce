<?php

namespace Oro\Bundle\RedirectBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroRedirectBundle implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 10;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createOroRedirectScopeTable($schema);
        $this->addOroRedirectScopeForeignKeys($schema);
        $queries->addPostQuery(new RedirectWebsitesToScopesQuery());
    }

    /**
     * Create oro_redirect_scope table
     *
     * @param Schema $schema
     */
    protected function createOroRedirectScopeTable(Schema $schema)
    {
        $table = $schema->createTable('oro_redirect_scope');
        $table->addColumn('redirect_id', 'integer', []);
        $table->addColumn('scope_id', 'integer', []);
        $table->setPrimaryKey(['redirect_id', 'scope_id']);
    }

    /**
     * Add oro_redirect_scope foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroRedirectScopeForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_redirect_scope');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_redirect'),
            ['redirect_id'],
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
