<?php

namespace Oro\Bundle\InventoryBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtension;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Fallback\Provider\CategoryFallbackProvider;
use Oro\Bundle\CatalogBundle\Fallback\Provider\ParentCategoryFallbackProvider;
use Oro\Bundle\ConfigBundle\Migration\RenameConfigSectionQuery;
use Oro\Bundle\EntityBundle\Fallback\Provider\SystemConfigFallbackProvider;
use Oro\Bundle\EntityBundle\Migration\AddFallbackRelationTrait;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigEntityValueQuery;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\MigrationConstraintTrait;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ProductBundle\Entity\Product;

class OroInventoryBundle implements
    Installation,
    ExtendExtensionAwareInterface,
    RenameExtensionAwareInterface,
    ActivityExtensionAwareInterface
{
    use MigrationConstraintTrait;
    use AddFallbackRelationTrait;

    const INVENTORY_LEVEL_TABLE_NAME = 'oro_inventory_level';
    const OLD_WAREHOUSE_INVENTORY_TABLE = 'oro_warehouse_inventory_lev';
    const ORO_B2B_WAREHOUSE_INVENTORY_TABLE = 'orob2b_warehouse_inventory_lev';
    const WAREHOUSE_TABLE = 'oro_warehouse';
    const WAREHOUSE_TABLE_BETA1 = 'orob2b_warehouse';
    const NOTE_TABLE = 'oro_note';
    const ORDER_TABLE = 'oro_order';
    const ORDER_LINE_ITEM_TABLE = 'oro_order_line_item';
    const NOTE_WAREHOUSE_ASSOCIATION = 'warehouse_c913b87';
    const NOTE_WAREHOUSE_ASSOCIATION_COLUMN = 'warehouse_c913b87_id';
    const NOTE_WAREHOUSE_ASSOCIATION_COLUMN_BETA1 = 'warehouse_6eca7547_id';
    const ACTIVITY_LIST_WAREHOUSE_ASSOCIATION = 'warehouse_901db874';
    const ORDER_WAREHOUSE_ASSOCIATION = 'warehouse';
    const ORDER_WAREHOUSE_ASSOCIATION_COLUMN = 'warehouse_id';

    /** @var  ActivityExtension */
    protected $activityExtension;

    /** @var ExtendExtension */
    protected $extendExtension;

    /**
     * @var RenameExtension
     */
    private $renameExtension;

    /**
     * @inheritDoc
     */
    public function getMigrationVersion()
    {
        return 'v1_0';
    }

    /**
     * {@inheritDoc}
     */
    public function setActivityExtension(ActivityExtension $activityExtension)
    {
        $this->activityExtension = $activityExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }

    /**
     * @inheritDoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addManageInventoryFieldToProduct($schema);
        $this->addManageInventoryFieldToCategory($schema);

        $this->updateWarehouseEntityRelations($schema);

        if (($schema->hasTable(self::OLD_WAREHOUSE_INVENTORY_TABLE) ||
                $schema->hasTable(self::ORO_B2B_WAREHOUSE_INVENTORY_TABLE))
            && !$schema->hasTable(self::INVENTORY_LEVEL_TABLE_NAME)
        ) {
            $this->renameTablesUpdateRelation($schema, $queries);

            return;
        }

        /** Tables generation **/
        $this->createOroInventoryLevelTable($schema);

        /** Foreign keys generation **/
        $this->addOroInventoryLevelForeignKeys($schema);

        $queries->addPostQuery(
            new RenameConfigSectionQuery('oro_warehouse', 'oro_inventory', 'manage_inventory')
        );
    }

    /**
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function renameTablesUpdateRelation(Schema $schema, QueryBag $queries)
    {
        $extension = $this->renameExtension;

        $toTable = self::INVENTORY_LEVEL_TABLE_NAME;
        $fromTable = self::OLD_WAREHOUSE_INVENTORY_TABLE;
        $indexToDrop = 'uidx_oro_wh_wh_inventory_lev';

        if ($schema->hasTable(self::ORO_B2B_WAREHOUSE_INVENTORY_TABLE)) {
            $fromTable = self::ORO_B2B_WAREHOUSE_INVENTORY_TABLE;
            $indexToDrop = 'uidx_orob2b_wh_wh_inventory_lev';
        }

        //rename table
        $extension->renameTable($schema, $queries, $fromTable, $toTable);

        $inventoryTable = $schema->getTable($fromTable);

        // drop warehouse indexes
        $inventoryTable->dropIndex($indexToDrop);

        // drop warehouse column
        $warehouseForeignKey = $this->getConstraintName($inventoryTable, 'warehouse_id');
        $inventoryTable->removeForeignKey($warehouseForeignKey);
        $inventoryTable->dropColumn('warehouse_id');

        $this->addEntityConfigUpdateQueries($queries);
    }

    protected function updateWarehouseEntityRelations(Schema $schema)
    {
        if (class_exists('Oro\Bundle\WarehouseBundle\Entity\Warehouse')) {
            return;
        }

        $table = $this->getWarehouseTable($schema);
        if (!$table) {
            return;
        }

        $this->removeWarehouseNotesAssociation($schema, $table);

        if ($schema->getTable(self::ORDER_TABLE)->hasColumn(self::NOTE_WAREHOUSE_ASSOCIATION_COLUMN_BETA1)) {
            $this->dropForeignKeyAndColumn($schema, self::ORDER_TABLE, self::NOTE_WAREHOUSE_ASSOCIATION_COLUMN_BETA1);
        }
        if ($schema->getTable(self::ORDER_TABLE)->hasColumn(self::ORDER_WAREHOUSE_ASSOCIATION_COLUMN)) {
            $this->dropForeignKeyAndColumn($schema, self::ORDER_TABLE, self::ORDER_WAREHOUSE_ASSOCIATION_COLUMN);
        }
        if ($schema->getTable(self::ORDER_LINE_ITEM_TABLE)->hasColumn(self::ORDER_WAREHOUSE_ASSOCIATION_COLUMN)) {
            $this->dropForeignKeyAndColumn(
                $schema,
                self::ORDER_LINE_ITEM_TABLE,
                self::ORDER_WAREHOUSE_ASSOCIATION_COLUMN
            );
        }

        $schema->dropTable($table);
    }

    /**
     * @param Schema $schema
     * @param string $table
     */
    protected function removeWarehouseNotesAssociation(Schema $schema, $table)
    {
        $notes = $schema->getTable(self::NOTE_TABLE);
        if ($notes->hasColumn(self::NOTE_WAREHOUSE_ASSOCIATION_COLUMN)) {
            $this->dropForeignKeyAndColumn($schema, self::NOTE_TABLE, self::NOTE_WAREHOUSE_ASSOCIATION_COLUMN);
        }
        if ($notes->hasColumn(self::NOTE_WAREHOUSE_ASSOCIATION_COLUMN_BETA1)) {
            $this->dropForeignKeyAndColumn($schema, self::NOTE_TABLE, self::NOTE_WAREHOUSE_ASSOCIATION_COLUMN_BETA1);
        }
        $associationTableName = $this->activityExtension->getAssociationTableName('oro_note', $table);
        if ($associationTableName) {
            $schema->dropTable($associationTableName);
        }
    }

    /**
     * @param Schema $schema
     * @return null|string
     */
    protected function getWarehouseTable(Schema $schema)
    {
        $table = null;
        if ($schema->hasTable(self::WAREHOUSE_TABLE)) {
            $table = self::WAREHOUSE_TABLE;
        }

        if ($schema->hasTable(self::WAREHOUSE_TABLE_BETA1)) {
            $table = self::WAREHOUSE_TABLE_BETA1;
        }

        return $table;
    }

    /**
     * @param Schema $schema
     * @param string $tableName
     * @param string $relationColumn
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function dropForeignKeyAndColumn(Schema $schema, $tableName, $relationColumn)
    {
        $table = $schema->getTable($tableName);
        $foreignKey = $this->getConstraintName($table, $relationColumn);
        $table->removeForeignKey($foreignKey);
        $table->dropColumn($relationColumn);
    }

    /**
     * Create oro_inventory_level table
     */
    protected function createOroInventoryLevelTable(Schema $schema)
    {
        $table = $schema->createTable(self::INVENTORY_LEVEL_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('quantity', 'decimal', ['precision' => 20, 'scale' => 10]);
        $table->addColumn('product_id', 'integer');
        $table->addColumn('product_unit_precision_id', 'integer');
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add oro_inventory_level foreign keys.
     */
    protected function addOroInventoryLevelForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::INVENTORY_LEVEL_TABLE_NAME);

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

    protected function addManageInventoryFieldToProduct(Schema $schema)
    {
        $this->addFallbackRelation(
            $schema,
            $this->extendExtension,
            'oro_product',
            'manageInventory',
            'oro.inventory.manage_inventory.label',
            [
                CategoryFallbackProvider::FALLBACK_ID => ['fieldName' => 'manageInventory'],
                SystemConfigFallbackProvider::FALLBACK_ID => [
                    'configName' => 'oro_inventory.manage_inventory'
                ],
            ]
        );
    }

    protected function addManageInventoryFieldToCategory(Schema $schema)
    {
        $this->addFallbackRelation(
            $schema,
            $this->extendExtension,
            'oro_catalog_category',
            'manageInventory',
            'oro.inventory.manage_inventory.label',
            [
                ParentCategoryFallbackProvider::FALLBACK_ID => ['fieldName' => 'manageInventory'],
                SystemConfigFallbackProvider::FALLBACK_ID => [
                    'configName' => 'oro_inventory.manage_inventory'
                ],
            ]
        );
    }

    protected function addEntityConfigUpdateQueries(QueryBag $queries)
    {
        $configData = [
            'id' => 'oro.inventory.inventorylevel.id.label',
            'product' => 'oro.inventory.inventorylevel.product.label',
            'quantity' => 'oro.inventory.inventorylevel.quantity.label',
            'productUnitPrecision' => 'oro.inventory.inventorylevel.product_unit_precision.label',
            'warehouse' => 'oro.inventory.inventorylevel.warehouse.label',
        ];
        $this->addEntityFieldLabelConfigs($queries, InventoryLevel::class, $configData);

        $configData = ['manageInventory' => 'oro.inventory.manage_inventory.label'];
        $this->addEntityFieldLabelConfigs($queries, Product::class, $configData);
        $this->addEntityFieldLabelConfigs($queries, Category::class, $configData);

        $queries->addPostQuery(new UpdateEntityConfigEntityValueQuery(
            InventoryLevel::class,
            'entity',
            'label',
            'oro.inventory.inventorylevel.entity_label'
        ));

        $queries->addPostQuery(new UpdateEntityConfigEntityValueQuery(
            InventoryLevel::class,
            'entity',
            'plural_label',
            'oro.inventory.inventorylevel.entity_plural_label'
        ));

        $queries->addPostQuery(new UpdateEntityConfigExtendClassQuery(
            InventoryLevel::class,
            'Extend\Entity\EX_OroWarehouseBundle_WarehouseInventoryLevel',
            'Extend\Entity\EX_OroInventoryBundle_InventoryLevel'
        ));

        $queries->addPostQuery(new UpdateFallbackEntitySystemOptionConfig(
            Product::class,
            'manageInventory',
            'oro_inventory.manage_inventory'
        ));
        $queries->addPostQuery(new UpdateFallbackEntitySystemOptionConfig(
            Category::class,
            'manageInventory',
            'oro_inventory.manage_inventory'
        ));
    }

    protected function addEntityFieldLabelConfigs(QueryBag $queries, $class, $data)
    {
        foreach ($data as $fieldName => $value) {
            $queries->addPostQuery(new UpdateEntityConfigFieldValueQuery(
                $class,
                $fieldName,
                'entity',
                'label',
                $value
            ));
        }
    }
}
