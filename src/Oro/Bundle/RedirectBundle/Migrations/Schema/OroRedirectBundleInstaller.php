<?php

namespace Oro\Bundle\RedirectBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroRedirectBundleInstaller implements Installation
{
    #[\Override]
    public function getMigrationVersion(): string
    {
        return 'v1_8';
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        /** Tables generation **/
        $this->createOroRedirectSlugTable($schema);
        $this->createOroRedirectTable($schema);
        $this->createOroSlugScopeTable($schema);
        $this->createOroRedirectScopeTable($schema);

        /** Foreign keys generation **/
        $this->addOroRedirectForeignKeys($schema);
        $this->addOroSlugScopeForeignKeys($schema);
        $this->addOroRedirectScopeForeignKeys($schema);
        $this->addOroRedirectSlugForeignKeys($schema);
        $this->addUniqueConstraintToOroRedirectTable($queries);
    }

    /**
     * Create oro_redirect_slug table
     */
    private function createOroRedirectSlugTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_redirect_slug');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('localization_id', 'integer', ['notnull' => false]);
        $table->addColumn('url_hash', 'string', ['length' => 32]);
        $table->addColumn('route_name', 'string', ['length' => 255]);
        $table->addColumn('url', 'string', ['length' => 1024]);
        $table->addColumn('slug_prototype', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('route_parameters', 'array', ['comment' => '(DC2Type:array)']);
        $table->addColumn('parameters_hash', 'string', ['length' => 32]);
        $table->addColumn('scopes_hash', 'string', ['length' => 32]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['url_hash'], 'oro_redirect_slug_url_hash');
        $table->addIndex(['route_name'], 'oro_redirect_slug_route');
        $table->addIndex(['slug_prototype'], 'oro_redirect_slug_slug');
        $table->addIndex(['parameters_hash'], 'oro_redirect_slug_parameters_hash_idx');

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    private function addUniqueConstraintToOroRedirectTable(QueryBag $queries): void
    {
        $queries->addPostQuery(
            'ALTER TABLE oro_redirect_slug ADD CONSTRAINT oro_redirect_slug_deferrable_uidx ' .
            'UNIQUE(organization_id, url_hash, scopes_hash) DEFERRABLE INITIALLY DEFERRED'
        );
    }

    /**
     * Create oro_slug_scope table
     */
    private function createOroSlugScopeTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_slug_scope');
        $table->addColumn('slug_id', 'integer');
        $table->addColumn('scope_id', 'integer');
        $table->setPrimaryKey(['slug_id', 'scope_id']);
    }

    /**
     * Create oro_redirect_scope table
     */
    private function createOroRedirectScopeTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_redirect_scope');
        $table->addColumn('redirect_id', 'integer');
        $table->addColumn('scope_id', 'integer');
        $table->setPrimaryKey(['redirect_id', 'scope_id']);
    }

    /**
     * Add oro_slug_scope foreign keys.
     */
    private function addOroSlugScopeForeignKeys(Schema $schema): void
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

    /**
     * Add oro_redirect_scope foreign keys.
     */
    private function addOroRedirectScopeForeignKeys(Schema $schema): void
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

    /**
     * Create orob2b_redirect table
     */
    private function createOroRedirectTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_redirect');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('slug_id', 'integer', ['notnull' => false]);
        $table->addColumn('redirect_from_prototype', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('redirect_from', 'string', ['notnull' => true, 'length' => 1024]);
        $table->addColumn('redirect_to_prototype', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('redirect_to', 'string', ['notnull' => true, 'length' => 1024]);
        $table->addColumn('redirect_type', 'integer', ['notnull' => true]);
        $table->addColumn('from_hash', 'string', ['length' => 32]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['from_hash'], 'idx_oro_redirect_from_hash');
        $table->addIndex(['redirect_from_prototype'], 'idx_oro_redirect_redirect_from_prototype');
    }

    /**
     * Add oro_redirect foreign keys.
     */
    private function addOroRedirectForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_redirect');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_redirect_slug'),
            ['slug_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_redirect_slug foreign keys.
     */
    private function addOroRedirectSlugForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_redirect_slug');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_localization'),
            ['localization_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
