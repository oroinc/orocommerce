<?php

namespace OroB2B\Bundle\RFPAdminBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BRFPAdminBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOrob2BRfpRequestProductTable($schema);
        $this->createOrob2BRfpRequestProductItemTable($schema);

        /** Foreign keys generation **/
        $this->addOrob2BRfpRequestProductForeignKeys($schema);
        $this->addOrob2BRfpRequestProductItemForeignKeys($schema);
    }

    /**
     * Create orob2b_rfp_request_product table
     *
     * @param Schema $schema
     */
    protected function createOrob2BRfpRequestProductTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_rfp_request_product');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => false]);
        $table->addColumn('request_id', 'integer', ['notnull' => false]);
        $table->addColumn('product_sku', 'string', ['length' => 255]);
        $table->addColumn('comment', 'text', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['request_id'], 'IDX_B3DEE60D427EB8A5', []);
        $table->addIndex(['product_id'], 'IDX_B3DEE60D4584665A', []);
    }

    /**
     * Create orob2b_rfp_request_prod_item table
     *
     * @param Schema $schema
     */
    protected function createOrob2BRfpRequestProductItemTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_rfp_request_prod_item');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_unit_id', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('request_product_id', 'integer', ['notnull' => false]);
        $table->addColumn('quantity', 'float', []);
        $table->addColumn('product_unit_code', 'string', ['length' => 255]);
        $table->addColumn('value', 'money', ['precision' => 19, 'scale' => 4, 'comment' => '(DC2Type:money)']);
        $table->addColumn('currency', 'string', ['length' => 3]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['request_product_id'], 'IDX_1456A11D7DA1E0', []);
        $table->addIndex(['product_unit_id'], 'IDX_1456A1129646BBD', []);
    }

    /**
     * Add orob2b_rfp_request_product foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BRfpRequestProductForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_rfp_request_product');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_rfp_request'),
            ['request_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_rfp_request_prod_item foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BRfpRequestProductItemForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_rfp_request_prod_item');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product_unit'),
            ['product_unit_id'],
            ['code'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_rfp_request_product'),
            ['request_product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
