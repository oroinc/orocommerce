<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Migrations\Schema\v1_10;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ShoppingListBundle\Entity\ProductKitItemLineItem;

/**
 * Creates the table for {@see ProductKitItemLineItem} entity.
 */
class CreateOroShoppingListProductKitItemLineItemTable implements Migration
{
    public function up(Schema $schema, QueryBag $queries): void
    {
        if (!$schema->hasTable('oro_shopping_list_product_kit_item_line_item')) {
            $this->createOroShoppingListProductKitItemLineItemTable($schema);
            $this->addOroShoppingListProductKitItemLineItemForeignKeys($schema);
        }
    }

    private function createOroShoppingListProductKitItemLineItemTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_shopping_list_product_kit_item_line_item');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('line_item_id', 'integer');
        $table->addColumn('product_kit_item_id', 'integer');
        $table->addColumn('product_id', 'integer');
        $table->addColumn('unit_code', 'string', ['length' => 255]);
        $table->addColumn('quantity', 'float');
        $table->addColumn('sort_order', 'integer', ['default' => 0]);
        $table->setPrimaryKey(['id']);
    }

    private function addOroShoppingListProductKitItemLineItemForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_shopping_list_product_kit_item_line_item');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_shopping_list_line_item'),
            ['line_item_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
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
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product_unit'),
            ['unit_code'],
            ['code'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
