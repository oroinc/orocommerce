<?php

namespace OroB2B\Bundle\PricingBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BPricingBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOrob2BPriceListCurrencyTable($schema);
        $this->createOrob2BPriceListTable($schema);
        $this->createOrob2BPriceListToWebsiteTable($schema);
        $this->createOrob2BPriceListToCustomerTable($schema);
        $this->createOrob2BPriceListToCustomerGroupTable($schema);
        $this->createOroB2BPriceProductTable($schema);

        /** Foreign keys generation **/
        $this->addOrob2BPriceListCurrencyForeignKeys($schema);
        $this->addOrob2BPriceListToWebsiteForeignKeys($schema);
        $this->addOrob2BPriceListToCustomerForeignKeys($schema);
        $this->addOrob2BPriceListToCustomerGroupForeignKeys($schema);
        $this->addOroB2BPriceProductForeignKeys($schema);
    }

    /**
     * @param Schema $schema
     */
    protected function createOrob2BPriceListCurrencyTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_price_list_currency');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('price_list_id', 'integer', []);
        $table->addColumn('currency', 'string', ['length' => 3]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * @param Schema $schema
     */
    protected function createOrob2BPriceListTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_price_list');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('is_default', 'boolean', []);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create orob2b_price_product table
     *
     * @param Schema $schema
     */
    protected function createOroB2BPriceProductTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_price_product');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('price_list_id', 'integer', []);
        $table->addColumn('product_id', 'integer', []);
        $table->addColumn('unit_code', 'string', ['length' => 255]);
        $table->addColumn('product_sku', 'string', ['length' => 255]);
        $table->addColumn('quantity', 'float', []);
        $table->addColumn('value', 'float', []);
        $table->addColumn('currency', 'string', ['length' => 3]);
        $table->addUniqueIndex(
            ['product_id', 'price_list_id', 'quantity', 'unit_code', 'currency'],
            'orob2b_pricing_price_list_uidx'
        );
        $table->addIndex(['price_list_id'], 'idx_bcde766d5688ded7', []);
        $table->addIndex(['product_id'], 'idx_bcde766d4584665a', []);
        $table->addIndex(['unit_code'], 'idx_bcde766dfbd3d1c2', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * @param Schema $schema
     */
    protected function createOrob2BPriceListToWebsiteTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_price_list_to_website');
        $table->addColumn('price_list_id', 'integer', []);
        $table->addColumn('website_id', 'integer', []);
        $table->setPrimaryKey(['price_list_id', 'website_id']);
        $table->addUniqueIndex(['website_id'], 'uniq_8f1e263218f45c82');
    }

    /**
     * @param Schema $schema
     */
    protected function createOrob2BPriceListToCustomerTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_price_list_to_customer');
        $table->addColumn('price_list_id', 'integer', []);
        $table->addColumn('customer_id', 'integer', []);
        $table->setPrimaryKey(['price_list_id', 'customer_id']);
        $table->addUniqueIndex(['customer_id'], 'uniq_7748d9299395c3f3');
    }

    /**
     * @param Schema $schema
     */
    protected function createOrob2BPriceListToCustomerGroupTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_price_list_to_c_group');
        $table->addColumn('price_list_id', 'integer', []);
        $table->addColumn('customer_group_id', 'integer', []);
        $table->setPrimaryKey(['price_list_id', 'customer_group_id']);
        $table->addUniqueIndex(['customer_group_id'], 'uniq_4ca0ef88d2919a68');
    }

    /**
     * @param Schema $schema
     */
    protected function addOrob2BPriceListCurrencyForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_price_list_currency');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_list'),
            ['price_list_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * @param Schema $schema
     */
    protected function addOrob2BPriceListToWebsiteForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_price_list_to_website');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_website'),
            ['website_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_list'),
            ['price_list_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * @param Schema $schema
     */
    protected function addOrob2BPriceListToCustomerForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_price_list_to_customer');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_customer'),
            ['customer_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_list'),
            ['price_list_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * @param Schema $schema
     */
    protected function addOrob2BPriceListToCustomerGroupForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_price_list_to_c_group');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_customer_group'),
            ['customer_group_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_list'),
            ['price_list_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add orob2b_price_product foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BPriceProductForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_price_product');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_list'),
            ['price_list_id'],
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
