<?php

namespace OroB2B\Bundle\CatalogBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BCatalogBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOrob2BCatalogCategoryTable($schema);
        $this->createOrob2BCatalogCategoryTitleTable($schema);

        /** Foreign keys generation **/
        $this->addOrob2BCatalogCategoryForeignKeys($schema);
        $this->addOrob2BCatalogCategoryTitleForeignKeys($schema);
    }

    /**
     * Create orob2b_catalog_category table
     *
     * @param Schema $schema
     */
    protected function createOrob2BCatalogCategoryTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_catalog_category');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('parent_id', 'integer', ['notnull' => false]);
        $table->addColumn('tree_left', 'integer', []);
        $table->addColumn('tree_level', 'integer', []);
        $table->addColumn('tree_right', 'integer', []);
        $table->addColumn('tree_root', 'integer', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['parent_id'], 'idx_fbd712dd727aca70', []);
    }

    /**
     * Create orob2b_catalog_category_title table
     *
     * @param Schema $schema
     */
    protected function createOrob2BCatalogCategoryTitleTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_catalog_category_title');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('locale_id', 'integer', ['notnull' => false]);
        $table->addColumn('catalog_id', 'integer', ['notnull' => false]);
        $table->addColumn('value', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('fallback', 'string', ['notnull' => false, 'length' => 64]);
        $table->addIndex(['locale_id'], 'idx_179c42f5e559dfd1', []);
        $table->addIndex(['catalog_id'], 'idx_179c42f5cc3c66fc', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add orob2b_catalog_category foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BCatalogCategoryForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_catalog_category');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_catalog_category'),
            ['parent_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add orob2b_catalog_category_title foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BCatalogCategoryTitleForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_catalog_category_title');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_locale'),
            ['locale_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_catalog_category'),
            ['catalog_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
}
