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
        $this->changeOrob2BSaleQuoteProductItemTable($schema);

        /** Foreign keys changes **/
        $this->addOrob2BSaleQuoteForeignKeys($schema);
        $this->addOrob2BSaleQuoteProductItemForeignKeys($schema);
    }

    /**
     * Create orob2b_sale_quote table
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
     * Create orob2b_sale_quote_product_item table
     *
     * @param Schema $schema
     */
    protected function changeOrob2BSaleQuoteProductItemTable(Schema $schema)
    {
        $table = $schema->getTable('orob2b_sale_quote_product_item');
        $table->addColumn('request_product_item_id', 'integer', ['notnull' => false]);
        $table->addColumn('requested_product_unit_id', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('requested_quantity', 'float', []);
        $table->addColumn('requested_product_unit_code', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn(
            'requested_value',
            'money',
            ['precision' => 19, 'scale' => 4, 'comment' => '(DC2Type:money)']
        );
        $table->addColumn('requested_currency', 'string', ['length' => 255]);
        $table->addColumn('status', 'smallint', ['notnull' => false]);
        $table->addColumn('comment', 'text', ['notnull' => false]);
        $table->addIndex(['requested_product_unit_id'], 'IDX_3ED01F0A830A1BC3', []);
        $table->addIndex(['request_product_item_id'], 'IDX_3ED01F0AF0EE02B6', []);
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
     * Add orob2b_sale_quote_product_item foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BSaleQuoteProductItemForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_sale_quote_product_item');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_rfp_request_prod_item'),
            ['request_product_item_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product_unit'),
            ['requested_product_unit_id'],
            ['code'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
