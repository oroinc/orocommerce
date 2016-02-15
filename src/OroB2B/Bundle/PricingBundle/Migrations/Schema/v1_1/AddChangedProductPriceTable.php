<?php

namespace OroB2B\Bundle\PricingBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddChangedProductPriceTable implements Migration
{

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createOroB2BChangedProductPriceTable($schema);
        $this->addOroB2BChangedProductPriceForeignKeys($schema);
    }

    /**
     * Create orob2b_prod_price_ch_trigger table
     *
     * @param Schema $schema
     */
    protected function createOroB2BChangedProductPriceTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_prod_price_ch_trigger');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('price_list_id', 'integer', []);
        $table->addColumn('product_id', 'integer', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['product_id', 'price_list_id'], 'orob2b_changed_product_price_list_unq');
    }

    /**
     * Add orob2b_prod_price_ch_trigger foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BChangedProductPriceForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_prod_price_ch_trigger');
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
