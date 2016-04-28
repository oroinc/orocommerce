<?php

namespace OroB2B\Bundle\ShippingBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BShippingBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_0';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOrob2BShippingOrigWarehouseTable($schema);
        $this->createOrob2BShippingLengthUnitTable($schema);
        $this->createOrob2BShippingFreightClassTable($schema);
        $this->createOrob2BShippingWeightUnitTable($schema);
        $this->createOrob2BShippingProdUnitOptsTable($schema);

        /** Foreign keys generation **/
        $this->addOrob2BShippingOrigWarehouseForeignKeys($schema);
        $this->addOrob2BShippingProdUnitOptsForeignKeys($schema);
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
     * Create orob2b_shipping_length_unit table
     *
     * @param Schema $schema
     */
    protected function createOrob2BShippingLengthUnitTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_shipping_length_unit');
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
     * Create orob2b_shipping_prod_unit_opts table
     *
     * @param Schema $schema
     */
    protected function createOrob2BShippingProdUnitOptsTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_shipping_prod_unit_opts');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('freight_class_code', 'string', ['length' => 255]);
        $table->addColumn('length_unit_code', 'string', ['length' => 255]);
        $table->addColumn('weght_unit_code', 'string', ['length' => 255]);
        $table->addColumn('unit_code', 'string', ['length' => 255]);
        $table->addColumn('product_id', 'integer', []);
        $table->addColumn('weight', 'float', []);
        $table->addColumn('length', 'float', []);
        $table->addColumn('width', 'float', []);
        $table->addColumn('height', 'float', []);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addIndex(['product_id']);
        $table->addIndex(['weght_unit_code']);
        $table->addIndex(['length_unit_code']);
        $table->addUniqueIndex(
            ['product_id', 'unit_code'],
            'shipping_product_unit_options__product_id__unit_code__uidx'
        );
        $table->addIndex(['freight_class_code']);
        $table->addIndex(['unit_code']);
        $table->setPrimaryKey(['id']);
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

    /**
     * Add orob2b_shipping_prod_unit_opts foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BShippingProdUnitOptsForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_shipping_prod_unit_opts');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_shipping_freight_class'),
            ['freight_class_code'],
            ['code'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_shipping_length_unit'),
            ['length_unit_code'],
            ['code'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_shipping_weight_unit'),
            ['weght_unit_code'],
            ['code'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product_unit'),
            ['unit_code'],
            ['code'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product'),
            ['product_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
}
