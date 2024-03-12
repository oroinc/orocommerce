<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroCatalogBundle implements Migration, RenameExtensionAwareInterface
{
    use RenameExtensionAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->createConstraint(
            $schema,
            $queries,
            'orob2b_catalog_cat_long_desc',
            'oro_fallback_localization_val',
            ['localized_value_id']
        );
        $this->createConstraint(
            $schema,
            $queries,
            'orob2b_catalog_cat_short_desc',
            'oro_fallback_localization_val',
            ['localized_value_id']
        );
        $this->createConstraint(
            $schema,
            $queries,
            'orob2b_catalog_category_title',
            'oro_fallback_localization_val',
            ['localized_value_id']
        );
        $this->createOroCategoryDefaultProductOptionsTable($schema);
        $this->updateOroCategoryTable($schema);
        $this->addOroCategoryDefaultProductOptionsForeignKeys($schema, $queries);
        $this->addOroCategoryForeignKeys($schema);
    }

    private function createConstraint(
        Schema $schema,
        QueryBag $queries,
        string $tableName,
        string $foreignTable,
        array $fields
    ): void {
        $this->renameExtension->addForeignKeyConstraint(
            $schema,
            $queries,
            $tableName,
            $foreignTable,
            $fields,
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    private function createOroCategoryDefaultProductOptionsTable(Schema $schema): void
    {
        $table = $schema->createTable('orob2b_category_def_prod_opts');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_unit_code', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('product_unit_precision', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    private function updateOroCategoryTable(Schema $schema): void
    {
        $table = $schema->getTable('orob2b_catalog_category');
        $table->addColumn('default_product_options_id', 'integer', ['notnull' => false]);
        $table->addUniqueIndex(['default_product_options_id']);
    }

    private function addOroCategoryDefaultProductOptionsForeignKeys(Schema $schema, QueryBag $queries): void
    {
        $this->renameExtension->addForeignKeyConstraint(
            $schema,
            $queries,
            'orob2b_category_def_prod_opts',
            'oro_product_unit',
            ['product_unit_code'],
            ['code'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    private function addOroCategoryForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('orob2b_catalog_category');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_category_def_prod_opts'),
            ['default_product_options_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
