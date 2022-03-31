<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_27;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Creates ProductKitItem table and related tables.
 */
class CreateProductKitItem implements Migration
{
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->createOroProductKitItemTable($schema);
        $this->createOroProductKitItemLabelTable($schema);
        $this->createOroProductKitItemProductsTable($schema);
        $this->createOroProductKitItemReferencedProductUnitPrecisionsTable($schema);
    }

    protected function createOroProductKitItemTable(Schema $schema): void
    {
        if ($schema->hasTable('oro_product_kit_item')) {
            return;
        }

        $table = $schema->createTable('oro_product_kit_item');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->setPrimaryKey(['id']);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');

        $table->addColumn('optional', 'boolean', ['default' => false]);
        $table->addColumn('sort_order', 'integer', ['default' => 0]);
        $table->addColumn('minimum_quantity', 'float', ['notnull' => false]);
        $table->addColumn('maximum_quantity', 'float', ['notnull' => false]);

        $table->addColumn('unit_code', 'string', ['notnull' => false]);
        $table->addColumn('product_kit_id', 'integer', ['notnull' => false]);

        $this->addOroProductKitItemForeignKeys($schema);
    }

    protected function addOroProductKitItemForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_product_kit_item');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['product_kit_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product_unit'),
            ['unit_code'],
            ['code'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    protected function createOroProductKitItemLabelTable(Schema $schema): void
    {
        if ($schema->hasTable('oro_product_prod_kit_item_label')) {
            return;
        }

        $table = $schema->createTable('oro_product_prod_kit_item_label');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_kit_item_id', 'integer', ['notnull' => false]);
        $table->addColumn('localization_id', 'integer', ['notnull' => false]);
        $table->addColumn('fallback', 'string', ['notnull' => false, 'length' => 64]);
        $table->addColumn('string', 'string', ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['fallback'], 'idx_product_prod_kit_fallback', []);
        $table->addIndex(['string'], 'idx_product_prod_kit_string', []);

        $this->addOroProductKitItemLabelForeignKeys($schema);
    }

    protected function addOroProductKitItemLabelForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_product_prod_kit_item_label');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product_kit_item'),
            ['product_kit_item_id'],
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

    protected function createOroProductKitItemProductsTable(Schema $schema): void
    {
        if ($schema->hasTable('oro_product_kit_item_products')) {
            return;
        }

        $table = $schema->createTable('oro_product_kit_item_products');
        $table->addColumn('product_kit_item_id', 'integer', []);
        $table->addColumn('product_id', 'integer', []);
        $table->setPrimaryKey(['product_kit_item_id', 'product_id']);

        $this->addOroProductKitItemProductsForeignKeys($schema);
    }

    protected function addOroProductKitItemProductsForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_product_kit_item_products');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product_kit_item'),
            ['product_kit_item_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    protected function createOroProductKitItemReferencedProductUnitPrecisionsTable(Schema $schema): void
    {
        if ($schema->hasTable('oro_product_kit_unit_precisions')) {
            return;
        }

        $table = $schema->createTable('oro_product_kit_unit_precisions');
        $table->addColumn('product_kit_item_id', 'integer', []);
        $table->addColumn('product_unit_precision_id', 'integer', []);
        $table->setPrimaryKey(['product_kit_item_id', 'product_unit_precision_id']);

        $this->addOroProductKitItemReferencedProductUnitPrecisionsForeignKeys($schema);
    }

    protected function addOroProductKitItemReferencedProductUnitPrecisionsForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_product_kit_unit_precisions');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product_kit_item'),
            ['product_kit_item_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product_unit_precision'),
            ['product_unit_precision_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
