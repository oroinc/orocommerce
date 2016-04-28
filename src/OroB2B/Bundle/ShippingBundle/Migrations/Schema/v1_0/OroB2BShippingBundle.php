<?php

namespace OroB2B\Bundle\ShippingBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BShippingBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOrob2BShippingOrigWarehouseTable($schema);
        $this->createOrob2BShippingDimensionUnitTable($schema);
        $this->createOrob2BShippingFreightClassTable($schema);
        $this->createOrob2BShippingWeightUnitTable($schema);

        /** Foreign keys generation **/
        $this->addOrob2BShippingOrigWarehouseForeignKeys($schema);
    }

    /**
     * Create orob2b_shipping_orig_warehouse table
     *
     * @param Schema $schema
     */
    protected function createOrob2BShippingOrigWarehouseTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_shipping_orig_warehouse');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('region_code', 'string', ['notnull' => false, 'length' => 16]);
        $table->addColumn('warehouse_id', 'integer', []);
        $table->addColumn('country_code', 'string', ['notnull' => false, 'length' => 2]);
        $table->addColumn('label', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('street', 'string', ['notnull' => false, 'length' => 500]);
        $table->addColumn('street2', 'string', ['notnull' => false, 'length' => 500]);
        $table->addColumn('city', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('postal_code', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('organization', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('region_text', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('name_prefix', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('first_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('middle_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('last_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('name_suffix', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('created', 'datetime', []);
        $table->addColumn('updated', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['warehouse_id']);
        $table->addIndex(['country_code'], []);
        $table->addIndex(['region_code'], []);
    }

    /**
     * Create orob2b_shipping_dimension_unit table
     *
     * @param Schema $schema
     */
    protected function createOrob2BShippingDimensionUnitTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_shipping_dimension_unit');
        $table->addColumn('code', 'string', ['length' => 255]);
        $table->addColumn('conversion_rates', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->setPrimaryKey(['code']);
    }

    /**
     * Create orob2b_shipping_freight_class table
     *
     * @param Schema $schema
     */
    protected function createOrob2BShippingFreightClassTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_shipping_freight_class');
        $table->addColumn('code', 'string', ['length' => 255]);
        $table->setPrimaryKey(['code']);
    }

    /**
     * Create orob2b_shipping_weight_unit table
     *
     * @param Schema $schema
     */
    protected function createOrob2BShippingWeightUnitTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_shipping_weight_unit');
        $table->addColumn('code', 'string', ['length' => 255]);
        $table->addColumn('conversion_rates', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->setPrimaryKey(['code']);
    }

    /**
     * Add orob2b_shipping_orig_warehouse foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BShippingOrigWarehouseForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_shipping_orig_warehouse');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_dictionary_region'),
            ['region_code'],
            ['combined_code'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_warehouse'),
            ['warehouse_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_dictionary_country'),
            ['country_code'],
            ['iso2_code'],
            ['onDelete' => null, 'onUpdate' => null]
        );
    }
}
