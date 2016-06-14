<?php

namespace OroB2B\Bundle\ProductBundle\Migrations\Schema\v1_5;

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
        $this->addOroB2BProductForeignKeys($schema);
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
        $table->addUniqueIndex(['primary_unit_precision_id'], 'idx_orob2b_product_primary_unit_precision_id');
    }

    /**
     * Add orob2b_product foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BProductForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::PRODUCT_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::PRODUCT_UNIT_PRECISION_TABLE_NAME),
            ['primary_unit_precision_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
