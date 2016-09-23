<?php

namespace Oro\Bundle\WarehouseBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\CatalogBundle\Fallback\Provider\CategoryFallbackProvider;
use Oro\Bundle\EntityBundle\Fallback\Provider\SystemConfigFallbackProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtension;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtensionAwareInterface;

class OroWarehouseBundleInstaller implements Installation, NoteExtensionAwareInterface, ExtendExtensionAwareInterface
{
    const WAREHOUSE_TABLE_NAME = 'oro_warehouse';
    const WAREHOUSE_INVENTORY_LEVEL_TABLE_NAME = 'oro_warehouse_inventory_lev';

    const ORDER_TABLE_NAME = 'oro_order';
    const ORDER_LINE_ITEM_TABLE_NAME = 'oro_order_line_item';

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
        $this->createOroWarehouseTable($schema);
        $this->createOroWarehouseInventoryLevelTable($schema);

        /** Foreign keys generation **/
        $this->addOroWarehouseForeignKeys($schema);
        $this->addOroWarehouseInventoryLevelForeignKeys($schema);

        /** Extended fields **/
        $this->addWarehouseRelations($schema);
        $this->addManageInventoryFieldToProduct($schema);
    }

    /**
     * Create oro_warehouse table
     *
     * @param Schema $schema
     */
    protected function createOroWarehouseTable(Schema $schema)
    {
        $table = $schema->createTable(self::WAREHOUSE_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('business_unit_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['created_at'], 'idx_oro_warehouse_created_at', []);
        $table->addIndex(['updated_at'], 'idx_oro_warehouse_updated_at', []);

        $this->noteExtension->addNoteAssociation($schema, $table->getName());
    }

    /**
     * Create oro_warehouse_inventory_level table
     *
     * @param Schema $schema
     */
    protected function createOroWarehouseInventoryLevelTable(Schema $schema)
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
            'uidx_oro_wh_wh_inventory_lev'
        );
    }

    /**
     * Add oro_warehouse foreign keys.
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
     * Add oro_warehouse_inventory_level foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroWarehouseInventoryLevelForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::WAREHOUSE_INVENTORY_LEVEL_TABLE_NAME);

        /** WAREHOUSE */
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_warehouse'),
            ['warehouse_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );

        /** PRODUCT */
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );

        /** PRODUCT UNIT PRECISION */
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product_unit_precision'),
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
                'extend' => ['owner' => ExtendScope::OWNER_CUSTOM, 'without_default' => true],
            ]
        );

        $this->extendExtension->addManyToOneRelation(
            $schema,
            $orderLineItemTable,
            'warehouse',
            $warehouseTable,
            'id',
            [
                'extend' => ['owner' => ExtendScope::OWNER_CUSTOM, 'without_default' => true],
            ]
        );
    }

    /**
     * @param Schema $schema
     */
    protected function addManageInventoryFieldToProduct(Schema $schema)
    {
        $productTable = $schema->getTable('oro_product');
        $fallbackTable = $schema->getTable('oro_entity_fallback_value');
        $this->extendExtension->addManyToOneRelation(
            $schema,
            $productTable,
            'manageInventory',
            $fallbackTable,
            'id',
            [
                'extend' => [
                    'owner' => ExtendScope::OWNER_CUSTOM,
                    'cascade' => ['all'],
                ],
                'form' => [
                    'is_enabled' => false,
                ],
                'view' => [
                    'is_displayable' => false,
                ],
                'fallback' => [
                    'fallbackList' => [
                        CategoryFallbackProvider::FALLBACK_ID => ['fieldName' => 'manageInventory'],
                        SystemConfigFallbackProvider::FALLBACK_ID => ['configName' => 'oro_warehouse.manage_inventory'],
                    ],
                ],
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
