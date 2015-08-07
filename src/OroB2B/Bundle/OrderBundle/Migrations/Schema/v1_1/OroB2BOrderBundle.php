<?php

namespace OroB2B\Bundle\OrderBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BOrderBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->changeOroB2BOrderTable($schema);
        $this->createOroB2BOrderProductTable($schema);
        $this->createOroB2BOrderProdItemTable($schema);

        /** Foreign keys generation **/
        $this->addOroB2BOrderForeignKeys($schema);
        $this->addOroB2BOrderProductForeignKeys($schema);
        $this->addOroB2BOrderProdItemForeignKeys($schema);
    }

    /**
     * Create orob2b_order table
     *
     * @param Schema $schema
     */
    protected function changeOroB2BOrderTable(Schema $schema)
    {
        $table = $schema->getTable('orob2b_order');
        $table->addColumn('quote_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_user_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_id', 'integer', ['notnull' => false]);
    }

    /**
     * Create orob2b_order_product table
     *
     * @param Schema $schema
     */
    protected function createOroB2BOrderProductTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_order_product');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => false]);
        $table->addColumn('order_id', 'integer', ['notnull' => false]);
        $table->addColumn('product_sku', 'string', ['length' => 255]);
        $table->addColumn('comment', 'text', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create orob2b_order_prod_item table
     *
     * @param Schema $schema
     */
    protected function createOroB2BOrderProdItemTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_order_prod_item');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('quote_product_offer_id', 'integer', ['notnull' => false]);
        $table->addColumn('product_unit_id', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('order_product_id', 'integer', ['notnull' => false]);
        $table->addColumn('quantity', 'float', ['notnull' => false]);
        $table->addColumn('product_unit_code', 'string', ['length' => 255]);
        $table->addColumn(
            'value',
            'money',
            ['notnull' => false, 'precision' => 19, 'scale' => 4, 'comment' => '(DC2Type:money)']
        );
        $table->addColumn('currency', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('price_type', 'smallint', []);
        $table->addColumn('from_quote', 'boolean', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add orob2b_order foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BOrderForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_order');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_sale_quote'),
            ['quote_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account_user'),
            ['account_user_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account'),
            ['account_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_order_product foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BOrderProductForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_order_product');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_order'),
            ['order_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_order_prod_item foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BOrderProdItemForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_order_prod_item');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_sale_quote_prod_offer'),
            ['quote_product_offer_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product_unit'),
            ['product_unit_id'],
            ['code'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_order_product'),
            ['order_product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
