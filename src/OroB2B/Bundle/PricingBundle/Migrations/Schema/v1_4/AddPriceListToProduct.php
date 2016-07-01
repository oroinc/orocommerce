<?php

namespace OroB2B\Bundle\PricingBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddPriceListToProduct implements Migration
{
    /**
     * @inheritDoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOrob2BPriceListToProductTable($schema);

        /** Foreign keys generation **/
        $this->addOrob2BPriceListToProductForeignKeys($schema);
        
        $queries->addPostQuery(new FillPriceListToProduct());
    }

    /**
     * Create orob2b_price_list_to_product table
     *
     * @param Schema $schema
     */
    protected function createOrob2BPriceListToProductTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_price_list_to_product');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', []);
        $table->addColumn('price_list_id', 'integer', []);
        $table->addColumn('is_manual', 'boolean', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['product_id', 'price_list_id'], 'orob2b_price_list_to_product_uidx');
        $table->addIndex(['price_list_id'], 'IDX_1B3B7F785688DED7', []);
        $table->addIndex(['product_id'], 'IDX_1B3B7F784584665A', []);
    }

    /**
     * Add orob2b_price_list_to_product foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BPriceListToProductForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_price_list_to_product');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_list'),
            ['price_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
