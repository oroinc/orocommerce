<?php

namespace OroB2B\Bundle\SaleBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BSaleBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOrob2BSaleQuoteProductTable($schema);
        $this->createOrob2BSaleQuoteProductItemTable($schema);

        /** Foreign keys generation **/
        $this->addOrob2BSaleQuoteProductForeignKeys($schema);
        $this->addOrob2BSaleQuoteProductItemForeignKeys($schema);
    }

    /**
     * Create orob2b_sale_quote_product table
     *
     * @param Schema $schema
     */
    protected function createOrob2BSaleQuoteProductTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_sale_quote_product');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => false]);
        $table->addColumn('product_sku', 'string', ['length' => 255]);
        $table->addColumn('quote_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['quote_id'], 'IDX_D9ADA158DB805178', []);
        $table->addIndex(['product_id'], 'IDX_D9ADA1584584665A', []);
    }

    /**
     * Create orob2b_sale_quote_product_item table
     *
     * @param Schema $schema
     */
    protected function createOrob2BSaleQuoteProductItemTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_sale_quote_product_item');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('quote_product_id', 'integer', ['notnull' => false]);
        $table->addColumn('product_unit_id', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('product_unit_code', 'string', ['length' => 255]);
        $table->addColumn('quantity', 'float', []);
        $table->addColumn('value', 'money', ['precision' => 19, 'scale' => 4, 'comment' => '(DC2Type:money)']);
        $table->addColumn('currency', 'string', ['length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['quote_product_id'], 'IDX_3ED01F0AF5D31CE1', []);
        $table->addIndex(['product_unit_id'], 'IDX_3ED01F0A29646BBD', []);
    }

    /**
     * Add orob2b_sale_quote_product foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BSaleQuoteProductForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_sale_quote_product');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_sale_quote'),
            ['quote_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_sale_quote_product_item foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BSaleQuoteProductItemForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_sale_quote_product_item');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product_unit'),
            ['product_unit_id'],
            ['code'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_sale_quote_product'),
            ['quote_product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
