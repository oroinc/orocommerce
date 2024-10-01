<?php

namespace Oro\Bundle\WebsiteSearchTermBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroWebsiteSearchTermBundleInstaller implements Installation
{
    #[\Override]
    public function getMigrationVersion(): string
    {
        return 'v1_0';
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        /** Tables generation **/
        $this->createSearchTermsTable($schema);
        $this->createSearchTermScopesTable($schema);

        /** Foreign keys generation **/
        $this->addRelationsForSearchTermTable($schema);
        $this->addRelationsForSearchTermScopesTable($schema);
    }

    private function createSearchTermsTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_website_search_search_term');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('phrases', 'text', ['notnull' => true]);
        $table->addColumn('action_type', 'string', ['notnull' => true, 'length' => 128]);
        $table->addColumn('modify_action_type', 'string', ['notnull' => false, 'length' => 128]);
        $table->addColumn('redirect_action_type', 'string', ['notnull' => false, 'length' => 128]);
        $table->addColumn('redirect_uri', 'text', ['notnull' => false]);
        $table->addColumn('redirect_system_page', 'text', ['notnull' => false]);
        $table->addColumn('redirect_301', 'boolean', ['notnull' => true, 'default' => false]);
        $table->addColumn('partial_match', 'boolean', ['notnull' => true, 'default' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('business_unit_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');

        $table->setPrimaryKey(['id']);
    }

    private function createSearchTermScopesTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_website_search_search_term_scopes');

        $table->addColumn('search_term_id', 'integer');
        $table->addColumn('scope_id', 'integer');

        $table->setPrimaryKey(['search_term_id', 'scope_id']);
    }

    private function addRelationsForSearchTermScopesTable(Schema $schema): void
    {
        $table = $schema->getTable('oro_website_search_search_term_scopes');

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_scope'),
            ['scope_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_website_search_search_term'),
            ['search_term_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    private function addRelationsForSearchTermTable(Schema $schema): void
    {
        $table = $schema->getTable('oro_website_search_search_term');

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_business_unit'),
            ['business_unit_owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
