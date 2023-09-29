<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v1_17;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Creates the table for {@see OrderProductKitItemLineItem} entity.
 */
class CreateOrderProductKitItemLineItemTable implements Migration
{
    public function up(Schema $schema, QueryBag $queries): void
    {
        if (!$schema->hasTable('oro_order_product_kit_item_line_item')) {
            $this->createOrderProductKitItemLineItemTable($schema);
            $this->addOrderProductKitItemLineItemForeignKeys($schema);
        }
    }

    private function createOrderProductKitItemLineItemTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_order_product_kit_item_line_item');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('line_item_id', 'integer');
        $table->addColumn('product_kit_item_id', 'integer', ['notnull' => false]);
        $table->addColumn('product_kit_item_label', 'string', ['length' => 255]);
        $table->addColumn('product_kit_item_optional', 'boolean', ['default' => false]);
        $table->addColumn('product_id', 'integer', ['notnull' => false]);
        $table->addColumn('product_sku', 'string', ['length' => 255]);
        $table->addColumn('product_name', 'string', ['length' => 255]);
        $table->addColumn('unit_code', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('product_unit_code', 'string', ['length' => 255]);
        $table->addColumn('quantity', 'float');
        $table->addColumn('sort_order', 'integer', ['default' => 0]);
        $table->addColumn(
            'value',
            'money',
            ['notnull' => false, 'precision' => 19, 'scale' => 4, 'comment' => '(DC2Type:money)']
        );
        $table->addColumn('currency', 'string', ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['id']);
    }

    private function addOrderProductKitItemLineItemForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_order_product_kit_item_line_item');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_order_line_item'),
            ['line_item_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product_kit_item'),
            ['product_kit_item_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product_unit'),
            ['unit_code'],
            ['code'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
