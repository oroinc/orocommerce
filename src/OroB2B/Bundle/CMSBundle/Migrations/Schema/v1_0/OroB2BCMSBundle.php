<?php

namespace OroB2B\Bundle\CMSBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BCMSBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroB2BCMSPageTable($schema);
        $this->createOroB2BCMSPageSlugTable($schema);

        /** Foreign keys generation **/
        $this->createOroB2BCMSPageForeignKeys($schema);
        $this->createOroB2BCMSPageSlugForeignKeys($schema);
    }

    /**
     * Create orob2b_cms_page table
     *
     * @param Schema $schema
     */
    protected function createOroB2BCMSPageTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_cms_page');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('parent_id', 'integer', ['notnull' => false]);
        $table->addColumn('current_slug_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('title', 'string', ['length' => 255]);
        $table->addColumn('content', 'text');
        $table->addColumn('tree_left', 'integer', []);
        $table->addColumn('tree_level', 'integer', []);
        $table->addColumn('tree_right', 'integer', []);
        $table->addColumn('tree_root', 'integer', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);

        $table->setPrimaryKey(['id']);

        $table->addIndex(['parent_id'], 'idx_64a9fc9e2aaa38', []);
        $table->addIndex(['current_slug_id'], 'idx_630cccaba7bf3f', []);
        $table->addIndex(['organization_id'], 'idx_af6a6ae1a9aa6f', []);
    }

    /**
     * Create orob2b_cms_page_to_slug table
     *
     * @param Schema $schema
     */
    protected function createOroB2BCMSPageSlugTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_cms_page_to_slug');
        $table->addColumn('page_id', 'integer', []);
        $table->addColumn('slug_id', 'integer', []);
        $table->setPrimaryKey(['page_id', 'slug_id']);

        $table->addIndex(['page_id'], 'idx_5c529a7f4e8a67', []);
        $table->addIndex(['slug_id'], 'idx_3da2af4fc13a17', []);
    }

    /**
     * Add orob2b_cms_page foreign keys.
     *
     * @param Schema $schema
     */
    protected function createOroB2BCMSPageForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_cms_page');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_cms_page'),
            ['parent_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_redirect_slug'),
            ['current_slug_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_cms_page_to_slug foreign keys.
     *
     * @param Schema $schema
     */
    protected function createOroB2BCMSPageSlugForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_cms_page_to_slug');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_cms_page'),
            ['page_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_redirect_slug'),
            ['slug_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
