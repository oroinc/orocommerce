<?php

namespace OroB2B\Bundle\WarehouseBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BWarehouseBundle implements Migration
{
    const WAREHOUSE_INVENTORY_LEVEL_TABLE_NAME = 'orob2b_warehouse_inventory_lev';

    /**
     * @inheritDoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroB2BWarehouseInventoryLevelTable($schema);

        /** Foreign keys generation **/
        $this->addOroB2BWarehouseInventoryLevelForeignKeys($schema);
    }

    /**
     * Create orob2b_warehouse_inventory_level table
     *
     * @param Schema $schema
     */
    public function createOroB2BWarehouseInventoryLevelTable(Schema $schema)
    {
        $table = $schema->createTable(self::WAREHOUSE_INVENTORY_LEVEL_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('quantity', 'decimal', ['scale' => 10]);
        $table->addColumn('warehouse_id', 'integer');
        $table->addColumn('product_id', 'integer');
        $table->addColumn('product_unit_precision_id', 'integer');
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['warehouse_id', 'product_unit_precision_id'], 'IDX_WAREHOUSE_INVENTORY');
    }

    /**
     * Add orob2b_warehouse_inventory_level foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BWarehouseInventoryLevelForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::WAREHOUSE_INVENTORY_LEVEL_TABLE_NAME);

        /** WAREHOUSE */
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_warehouse'),
            ['warehouse_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );

        /** PRODUCT */
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );

        /** PRODUCT UNIT PRECISION */
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product_unit_precision'),
            ['product_unit_precision_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
