<?php

namespace OroB2B\Bundle\OrderBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddLineItems implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createOrob2BOrderLineItemTable($schema);
        $this->addOrob2BOrderLineItemForeignKeys($schema);
        $this->updateOrderTable($schema);
    }

    /**
     * @param Schema $schema
     */
    protected function updateOrderTable(Schema $schema)
    {
        $table = $schema->getTable('orob2b_order');
        $table->addColumn('price_list_id', 'integer', ['notnull' => false]);
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_list'),
            ['price_list_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }

    /**
     * Create orob2b_order_line_item table
     *
     * @param Schema $schema
     */
    protected function createOrob2BOrderLineItemTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_order_line_item');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_unit_id', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('product_id', 'integer', ['notnull' => false]);
        $table->addColumn('order_id', 'integer', ['notnull' => false]);
        $table->addColumn('product_sku', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('free_form_product', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('quantity', 'float', ['notnull' => false]);
        $table->addColumn('product_unit_code', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn(
            'value',
            'money',
            ['notnull' => false, 'precision' => 19, 'scale' => 4, 'comment' => '(DC2Type:money)']
        );
        $table->addColumn('currency', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('price_type', 'integer', []);
        $table->addColumn('ship_by', 'date', ['notnull' => false, 'comment' => '(DC2Type:date)']);
        $table->addColumn('from_external_source', 'boolean', []);
        $table->addColumn('comment', 'text', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['product_id'], 'idx_de9136094584665a', []);
        $table->addIndex(['product_unit_id'], 'idx_de91360929646bbd', []);
        $table->addIndex(['order_id'], 'idx_de9136098d9f6d38', []);
    }

    /**
     * Add orob2b_order_line_item foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BOrderLineItemForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_order_line_item');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product_unit'),
            ['product_unit_id'],
            ['code'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product'),
            ['product_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_order'),
            ['order_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
}
