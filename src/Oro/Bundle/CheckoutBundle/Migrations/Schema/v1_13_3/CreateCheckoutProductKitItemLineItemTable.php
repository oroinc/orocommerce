<?php

declare(strict_types=1);

namespace Oro\Bundle\CheckoutBundle\Migrations\Schema\v1_13_3;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Creates the table for {@see CheckoutProductKitItemLineItem} entity.
 */
class CreateCheckoutProductKitItemLineItemTable implements Migration
{
    public function up(Schema $schema, QueryBag $queries): void
    {
        if (!$schema->hasTable('oro_checkout_product_kit_item_line_item')) {
            $this->createCheckoutProductKitItemLineItemTable($schema);
            $this->addCheckoutProductKitItemLineItemForeignKeys($schema);
        }
    }

    private function createCheckoutProductKitItemLineItemTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_checkout_product_kit_item_line_item');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('line_item_id', 'integer');
        $table->addColumn('product_kit_item_id', 'integer');
        $table->addColumn('product_id', 'integer');
        $table->addColumn('product_unit_id', 'string', ['length' => 255]);
        $table->addColumn('quantity', 'float');
        $table->addColumn('sort_order', 'integer', ['default' => 0]);
        $table->addColumn(
            'value',
            'money',
            ['notnull' => false, 'precision' => 19, 'scale' => 4, 'comment' => '(DC2Type:money)']
        );
        $table->addColumn('currency', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('is_price_fixed', 'boolean', ['notnull' => true, 'default' => false]);
        $table->setPrimaryKey(['id']);
    }

    private function addCheckoutProductKitItemLineItemForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_checkout_product_kit_item_line_item');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_checkout_line_item'),
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
            ['product_unit_id'],
            ['code'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
