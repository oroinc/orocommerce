<?php

namespace OroB2B\Bundle\WarehouseBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtension;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtensionAwareInterface;

class OroB2BWarehouseBundleInstaller implements Installation, NoteExtensionAwareInterface, ExtendExtensionAwareInterface
{
    const WAREHOUSE_TABLE_NAME = 'orob2b_warehouse';
    const WAREHOUSE_INVENTORY_LEVEL_TABLE_NAME = 'orob2b_warehouse_inventory_lev';

    const ORDER_TABLE_NAME = 'orob2b_order';
    const ORDER_LINE_ITEM_TABLE_NAME = 'orob2b_order_line_item';

    /** @var  NoteExtension */
    protected $noteExtension;

    /** @var ExtendExtension */
    protected $extendExtension;

    /**
     * {@inheritdoc}
     */
    public function setNoteExtension(NoteExtension $noteExtension)
    {
        $this->noteExtension = $noteExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * @inheritDoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroB2BWarehouseTable($schema);
        $this->createOroB2BWarehouseInventoryLevelTable($schema);

        /** Foreign keys generation **/
        $this->addOroB2BWarehouseForeignKeys($schema);
        $this->addOroB2BWarehouseInventoryLevelForeignKeys($schema);

        /** Extended fields **/
        $this->addWarehouseRelations($schema);
    }

    /**
     * Create orob2b_warehouse table
     *
     * @param Schema $schema
     */
    protected function createOroB2BWarehouseTable(Schema $schema)
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
     * Create orob2b_warehouse_inventory_level table
     *
     * @param Schema $schema
     */
    protected function createOroB2BWarehouseInventoryLevelTable(Schema $schema)
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
     * Add orob2b_warehouse foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BWarehouseForeignKeys(Schema $schema)
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

    /**
     * Add order related extended fields
     *
     * @param Schema $schema
     */
    protected function addWarehouseRelations(Schema $schema)
    {
        if (!$schema->hasTable(self::ORDER_TABLE_NAME) || !$schema->hasTable(self::ORDER_LINE_ITEM_TABLE_NAME)) {
            return;
        }

        $warehouseTable = $schema->getTable(self::WAREHOUSE_TABLE_NAME);
        $orderTable = $schema->getTable(self::ORDER_TABLE_NAME);
        $orderLineItemTable = $schema->getTable(self::ORDER_LINE_ITEM_TABLE_NAME);

        $this->extendExtension->addManyToOneRelation(
            $schema,
            $orderTable,
            'warehouse',
            $warehouseTable,
            'id',
            [
                'extend' => ['owner' => ExtendScope::OWNER_CUSTOM, 'without_default' => true]
            ]
        );

        $this->extendExtension->addManyToOneRelation(
            $schema,
            $orderLineItemTable,
            'warehouse',
            $warehouseTable,
            'id',
            [
                'extend' => ['owner' => ExtendScope::OWNER_CUSTOM, 'without_default' => true]
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function getMigrationVersion()
    {
        return 'v1_2';
    }
}
