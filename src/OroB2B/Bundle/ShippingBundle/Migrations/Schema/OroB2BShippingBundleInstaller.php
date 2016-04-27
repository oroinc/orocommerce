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
        $this->createOrob2BShippingDimensionUnitTable($schema);
        $this->createOrob2BShippingFreightClassTable($schema);
        $this->createOrob2BShippingWeightUnitTable($schema);
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
}
