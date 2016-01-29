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
     * Create orob2b_changed_product_price table
     *
     * @param Schema $schema
     */
    protected function createOroB2BChangedProductPriceTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_changed_product_price');
        $table->addColumn('price_list_id', 'integer', []);
        $table->addColumn('product_id', 'integer', []);
        $table->setPrimaryKey(['price_list_id', 'product_id']);
    }

    /**
     * Add orob2b_changed_product_price foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BChangedProductPriceForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_changed_product_price');
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
