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
        $this->createOroB2BriceListToProductTable($schema);

        /** Foreign keys generation **/
        $this->addOroB2BriceListToProductForeignKeys($schema);
    }

    /**
     * Create orob2b_price_list_to_product table
     *
     * @param Schema $schema
     */
    protected function createOroB2BriceListToProductTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_price_list_to_product');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', []);
        $table->addColumn('price_list_id', 'integer', []);
        $table->addColumn('is_manual', 'boolean', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['product_id', 'price_list_id'], 'orob2b_price_list_to_product_uidx');
    }

    /**
     * Add orob2b_price_list_to_product foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BriceListToProductForeignKeys(Schema $schema)
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
