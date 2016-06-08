<?php

namespace OroB2B\Bundle\PricingBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddPriceAttributes implements Migration
{
    /**
     * @inheritDoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroB2BPriceAttributeTable($schema);
        $this->createOroB2BPriceAttributeCurrencyTable($schema);
        $this->createOroB2BPriceAttributeProductPriceTable($schema);

        /** Foreign keys generation **/
        $this->addOroB2BPriceAttributeCurrencyForeignKeys($schema);
        $this->addOroB2BPriceAttributeProductPriceForeignKeys($schema);

    }

    /**
     * @param Schema $schema
     */
    protected function createOroB2BPriceAttributeTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_price_attribute_pl');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->setPrimaryKey(['id']);
    }

    /**
     * @param Schema $schema
     */
    protected function createOroB2BPriceAttributeCurrencyTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_product_attr_currency');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('price_attribute_id', 'integer', []);
        $table->addColumn('currency', 'string', ['length' => 3]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * @param Schema $schema
     */
    protected function createOroB2BPriceAttributeProductPriceTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_price_attribute_price');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('price_attribute_id', 'integer', []);
        $table->addColumn('product_id', 'integer', []);
        $table->addColumn('unit_code', 'string', ['length' => 255]);
        $table->addColumn('product_sku', 'string', ['length' => 255]);
        $table->addColumn('quantity', 'float', []);
        $table->addColumn('value', 'money', []);
        $table->addColumn('currency', 'string', ['length' => 3]);
        $table->addUniqueIndex(
            ['product_id', 'price_attribute_id', 'quantity', 'unit_code', 'currency'],
            'orob2b_pricing_price_attribute_uidx'
        );
        $table->setPrimaryKey(['id']);
    }

    /**
     * @param Schema $schema
     */
    protected function addOroB2BPriceAttributeCurrencyForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_product_attr_currency');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_attribute_pl'),
            ['price_attribute_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * @param Schema $schema
     */
    protected function addOroB2BPriceAttributeProductPriceForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_price_attribute_price');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_attribute_pl'),
            ['price_attribute_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product'),
            ['product_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product_unit'),
            ['unit_code'],
            ['code'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
}
