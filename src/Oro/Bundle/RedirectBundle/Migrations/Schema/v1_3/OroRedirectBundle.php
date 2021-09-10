<?php

namespace Oro\Bundle\RedirectBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
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
        $this->modifySlugTable($schema, $queries);
        $this->createOroRedirectScopeTable($schema);
        $this->addOroRedirectScopeForeignKeys($schema);
        $queries->addPostQuery(new RedirectWebsitesToScopesQuery());
    }

    /**
     * Create oro_redirect_scope table
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

    protected function modifySlugTable(Schema $schema, QueryBag $queries)
    {
        $queries->addPreQuery(
            new ParametrizedSqlMigrationQuery('DELETE FROM oro_redirect_slug WHERE route_name IS NULL')
        );

        $table = $schema->getTable('oro_redirect_slug');
        $table->addColumn('slug_prototype', 'string', ['length' => 255, 'notnull' => false]);

        $table->getColumn('route_name')->setNotnull(true);

        $table->addIndex(['route_name'], 'oro_redirect_slug_route', []);
        $table->addIndex(['slug_prototype'], 'oro_redirect_slug_slug', []);

        $queries->addPostQuery(
            new ParametrizedSqlMigrationQuery("UPDATE oro_redirect_slug s SET slug_prototype = CASE
              WHEN REVERSE(SUBSTR(REVERSE(s.url), 1, POSITION('/' IN SUBSTR(REVERSE(s.url), 1)) - 1)) = ''
                THEN NULL
                ELSE REVERSE(SUBSTR(REVERSE(s.url), 1, POSITION('/' IN SUBSTR(REVERSE(s.url), 1)) - 1)) 
              END")
        );
    }
}
