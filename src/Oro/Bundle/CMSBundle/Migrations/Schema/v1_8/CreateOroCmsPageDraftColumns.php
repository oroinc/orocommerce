<?php

namespace Oro\Bundle\CMSBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Add draft columns for oro_cms_page table.
 */
class CreateOroCmsPageDraftColumns implements Migration
{
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->createOroCmsPageDraftColumns($schema);
        $this->addOroCmsPageForeignKeys($schema);
    }

    private function createOroCmsPageDraftColumns(Schema $schema): void
    {
        $table = $schema->getTable('oro_cms_page');
        $table->addColumn('draft_project_id', 'integer', ['notnull' => false]);
        $table->addColumn('draft_source_id', 'integer', ['notnull' => false]);
        $table->addColumn('draft_uuid', 'guid', ['notnull' => false]);
        $table->addColumn('draft_owner_id', 'integer', ['notnull' => false]);
        $table->addIndex(['draft_project_id'], 'IDX_BCE4CB4A2E26AC0B');
        $table->addIndex(['draft_source_id'], 'IDX_BCE4CB4A953C1C61');
        $table->addIndex(['draft_owner_id'], 'IDX_BCE4CB4ADCA3D9F3');
    }

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
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['draft_owner_id'],
            ['id'],
            ['onDelete' => 'CASCADE']
        );
    }
}
