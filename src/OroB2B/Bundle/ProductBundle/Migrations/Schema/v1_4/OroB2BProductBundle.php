<?php

namespace OroB2B\Bundle\ProductBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BProductBundle implements Migration
{
    const PRODUCT_TABLE_NAME = 'orob2b_product';
    const PRODUCT_UNIT_PRECISION_TABLE_NAME = 'orob2b_product_unit_precision';

    public function up(Schema $schema, QueryBag $queries)
    {
        $this->updateOroB2BProductUnitPrecisionTable($schema);
        $this->updateOroB2BProductTable($schema);
    }

    /**
     * Update orob2b_product_unit_precision table
     *
     * @param Schema $schema
     */
    protected function updateOroB2BProductUnitPrecisionTable(Schema $schema)
    {
        $table = $schema->getTable(self::PRODUCT_UNIT_PRECISION_TABLE_NAME);
        $table->addColumn('conversion_rate', 'float', ['notnull' => false]);
        $table->addColumn('sell', 'boolean', ['notnull' => false]);
    }

    /**
     * Update orob2b_product table
     *
     * @param Schema $schema
     */
    protected function updateOroB2BProductTable(Schema $schema)
    {
        $table = $schema->getTable(self::PRODUCT_TABLE_NAME);
        $table->addColumn('primary_unit_precision_id', 'integer', ['notnull' => false]);
    }
}
