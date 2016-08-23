<?php

namespace Oro\Bundle\WarehouseBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtension;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtensionAwareInterface;

class OroWarehouseBundle implements Migration, NoteExtensionAwareInterface
{
    const WAREHOUSE_TABLE_NAME = 'orob2b_warehouse';
    const WAREHOUSE_INVENTORY_LEVEL_TABLE_NAME = 'orob2b_warehouse_inventory_lev';

    /** @var  NoteExtension */
    protected $noteExtension;

    /**
     * {@inheritdoc}
     */
    public function setNoteExtension(NoteExtension $noteExtension)
    {
        $this->noteExtension = $noteExtension;
    }

    /**
     * @inheritDoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroWarehouseTable($schema);
        $this->createOroWarehouseInventoryLevelTable($schema);

        /** Foreign keys generation **/
        $this->addOroWarehouseForeignKeys($schema);
        $this->addOroWarehouseInventoryLevelForeignKeys($schema);
    }

    /**
     * Create orob2b_warehouse table
     *
     * @param Schema $schema
     */
    public function createOroWarehouseTable(Schema $schema)
    {
        $table = $schema->createTable(self::WAREHOUSE_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('business_unit_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['created_at'], 'idx_orob2b_warehouse_created_at', []);
        $table->addIndex(['updated_at'], 'idx_orob2b_warehouse_updated_at', []);

        $this->noteExtension->addNoteAssociation($schema, $table->getName());
    }

    /**
     * Add orob2b_warehouse foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroWarehouseForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::WAREHOUSE_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_business_unit'),
            ['business_unit_owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Create orob2b_warehouse_inventory_level table
     *
     * @param Schema $schema
     */
    public function createOroWarehouseInventoryLevelTable(Schema $schema)
    {
        $table = $schema->createTable(self::WAREHOUSE_INVENTORY_LEVEL_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('quantity', 'decimal', ['precision' => 20, 'scale' => 10]);
        $table->addColumn('warehouse_id', 'integer');
        $table->addColumn('product_id', 'integer');
        $table->addColumn('product_unit_precision_id', 'integer');
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(
            ['warehouse_id', 'product_unit_precision_id'],
            'uidx_orob2b_wh_wh_inventory_lev'
        );
    }

    /**
     * Add orob2b_warehouse_inventory_level foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroWarehouseInventoryLevelForeignKeys(Schema $schema)
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
