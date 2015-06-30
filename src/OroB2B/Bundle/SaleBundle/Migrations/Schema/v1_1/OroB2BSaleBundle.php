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
        /** Tables changes **/
        $this->changeOrob2BSaleQuoteTable($schema);
        $this->changeOrob2BSaleQuoteProductTable($schema);
        $this->changeOrob2BSaleQuoteProductItemTable($schema);
        $this->createOrob2BSaleQuoteProdRequestTable($schema);

        /** Foreign keys changes **/
        $this->addOrob2BSaleQuoteForeignKeys($schema);
        $this->addOrob2BSaleQuoteProdRequestForeignKeys($schema);
    }

    /**
     * Change orob2b_sale_quote table
     *
     * @param Schema $schema
     */
    protected function changeOrob2BSaleQuoteTable(Schema $schema)
    {
        $table = $schema->getTable('orob2b_sale_quote');
        $table->addColumn('request_id', 'integer', ['notnull' => false]);
        $table->addIndex(['request_id'], 'IDX_4F66B6F6427EB8A5', []);
    }

    /**
     * Change orob2b_sale_quote_product table
     *
     * @param Schema $schema
     */
    protected function changeOrob2BSaleQuoteProductTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_sale_quote_product');
        $table->addColumn('product_replacement_id', 'integer', ['notnull' => false]);
        $table->addColumn('product_replacement_sku', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('type', 'smallint', ['notnull' => false]);
        $table->addColumn('comment', 'text', ['notnull' => false]);
        $table->addColumn('comment_customer', 'text', ['notnull' => false]);
    }

    /**
     * Create orob2b_sale_quote_product_item table
     *
     * @param Schema $schema
     */
    protected function changeOrob2BSaleQuoteProductItemTable(Schema $schema)
    {
        $table = $schema->getTable('orob2b_sale_quote_product_item');
        $table->addColumn('price_type', 'smallint', []);
        $table->addColumn('allow_increments', 'boolean', []);
        $table->setPrimaryKey(['id']);
        $schema->renameTable('orob2b_sale_quote_product_item', 'orob2b_sale_quote_prod_offer');
    }

    /**
     * Create orob2b_sale_quote_prod_request table
     *
     * @param Schema $schema
     */
    protected function createOrob2BSaleQuoteProdRequestTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_sale_quote_prod_request');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_unit_id', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('request_product_item_id', 'integer', ['notnull' => false]);
        $table->addColumn('quote_product_id', 'integer', ['notnull' => false]);
        $table->addColumn('quantity', 'float', []);
        $table->addColumn('product_unit_code', 'string', ['length' => 255]);
        $table->addColumn('value', 'money', ['precision' => 19, 'scale' => 4, 'comment' => '(DC2Type:money)']);
        $table->addColumn('currency', 'string', ['length' => 255]);
        $table->setPrimaryKey(['id']);
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
            ['product_replacement_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_sale_quote foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BSaleQuoteForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_sale_quote');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_rfp_request'),
            ['request_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_sale_quote_prod_request foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BSaleQuoteProdRequestForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_sale_quote_prod_request');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product_unit'),
            ['product_unit_id'],
            ['code'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_rfp_request_prod_item'),
            ['request_product_item_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_sale_quote_product'),
            ['quote_product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
