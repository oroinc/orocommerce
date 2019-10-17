<?php

namespace Oro\Bundle\CMSBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Add draft columns for oro_cms_page table.
 */
class CreateOroCmsPageDraftColumns implements Migration
{
    /**
     * @param Schema $schema
     * @param QueryBag $queries
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->createOroCmsPageDraftColumns($schema);
        $this->addOroCmsPageForeignKeys($schema);
    }

    /**
     * @param Schema $schema
     */
    private function createOroCmsPageDraftColumns(Schema $schema): void
    {
        $table = $schema->getTable('oro_cms_page');
        $table->addColumn('draft_project_id', 'integer', ['notnull' => false]);
        $table->addColumn('draft_source_id', 'integer', ['notnull' => false]);
        $table->addColumn('draft_uuid', 'guid', ['notnull' => false]);
        $table->addIndex(['draft_project_id'], 'IDX_BCE4CB4A2E26AC0B');
        $table->addIndex(['draft_source_id'], 'IDX_BCE4CB4A953C1C61');
    }

    /**
     * @param Schema $schema
     */
    private function addOroCmsPageForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_cms_page');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_draft_project'),
            ['draft_project_id'],
            ['id'],
            ['onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_cms_page'),
            ['draft_source_id'],
            ['id'],
            ['onDelete' => 'CASCADE']
        );
    }
}
