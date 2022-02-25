<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Schema\v1_17;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class CreateCategoryTables implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getOrder(): int
    {
        return 10;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        if ($schema->hasTable('oro_catalog_cat_title')) {
            return;
        }

        $this->createOroCatalogCategoryTitleTable($schema);
        $this->addOroCatalogCategoryTitleForeignKeys($schema);

        $this->createOroCatalogCategoryShortDescriptionTable($schema);
        $this->addOroCatalogCategoryShortDescriptionForeignKeys($schema);

        $this->createOroCatalogCategoryLongDescriptionTable($schema);
        $this->addOroCatalogCategoryLongDescriptionForeignKeys($schema);
    }

    /**
     * Create oro_catalog_cat_title table
     */
    private function createOroCatalogCategoryTitleTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_catalog_cat_title');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->addColumn('localization_id', 'integer', ['notnull' => false]);
        $table->addColumn('fallback', 'string', ['notnull' => false, 'length' => 64]);
        $table->addColumn('string', 'string', ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['fallback'], 'idx_cat_cat_title_fallback', []);
        $table->addIndex(['string'], 'idx_cat_cat_title_string', []);
    }

    /**
     * Add oro_catalog_cat_title foreign keys.
     */
    private function addOroCatalogCategoryTitleForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_catalog_cat_title');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_catalog_category'),
            ['category_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_localization'),
            ['localization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Create oro_catalog_cat_s_descr table
     */
    private function createOroCatalogCategoryShortDescriptionTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_catalog_cat_s_descr');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->addColumn('localization_id', 'integer', ['notnull' => false]);
        $table->addColumn('fallback', 'string', ['notnull' => false, 'length' => 64]);
        $table->addColumn('text', 'text', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['fallback'], 'idx_cat_cat_s_descr_fallback', []);
    }

    /**
     * Add oro_catalog_cat_s_descr foreign keys.
     */
    private function addOroCatalogCategoryShortDescriptionForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_catalog_cat_s_descr');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_catalog_category'),
            ['category_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_localization'),
            ['localization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Create oro_catalog_cat_l_descr table
     */
    private function createOroCatalogCategoryLongDescriptionTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_catalog_cat_l_descr');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->addColumn('localization_id', 'integer', ['notnull' => false]);
        $table->addColumn('fallback', 'string', ['notnull' => false, 'length' => 64]);
        $table->addColumn('wysiwyg', 'wysiwyg', ['notnull' => false, 'comment' => '(DC2Type:wysiwyg)']);
        $table->addColumn('wysiwyg_style', 'wysiwyg_style', ['notnull' => false]);
        $table->addColumn('wysiwyg_properties', 'wysiwyg_properties', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['fallback'], 'idx_cat_cat_l_descr_fallback', []);
    }

    /**
     * Add oro_catalog_cat_l_descr foreign keys.
     */
    private function addOroCatalogCategoryLongDescriptionForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_catalog_cat_l_descr');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_catalog_category'),
            ['category_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_localization'),
            ['localization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
}
