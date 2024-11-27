<?php

namespace Oro\Bundle\InventoryBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\CatalogBundle\Fallback\Provider\CategoryFallbackProvider;
use Oro\Bundle\CatalogBundle\Fallback\Provider\ParentCategoryFallbackProvider;
use Oro\Bundle\ConfigBundle\Migration\RenameConfigSectionQuery;
use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\EntityBundle\Fallback\Provider\SystemConfigFallbackProvider;
use Oro\Bundle\EntityBundle\Migration\AddFallbackRelationTrait;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigEntityValueQuery;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareTrait;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\InventoryBundle\Inventory\LowInventoryProvider;
use Oro\Bundle\InventoryBundle\Migrations\Schema\v1_0\UpdateEntityConfigExtendClassQuery;
use Oro\Bundle\InventoryBundle\Migrations\Schema\v1_0\UpdateFallbackEntitySystemOptionConfig;
use Oro\Bundle\InventoryBundle\Model\Inventory;
use Oro\Bundle\InventoryBundle\Provider\UpcomingProductProvider;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\MigrationConstraintTrait;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class OroInventoryBundleInstaller implements Installation, ExtendExtensionAwareInterface, RenameExtensionAwareInterface
{
    use MigrationConstraintTrait;
    use ExtendExtensionAwareTrait;
    use RenameExtensionAwareTrait;
    use AddFallbackRelationTrait;

    #[\Override]
    public function getMigrationVersion(): string
    {
        return 'v1_7';
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->addManageInventoryFieldToProduct($schema);
        $this->addManageInventoryFieldToCategory($schema);

        $this->addHighlightLowInventoryFieldToProduct($schema);
        $this->addHighlightLowInventoryFieldToCategory($schema);

        $this->addInventoryThresholdFieldToProduct($schema);
        $this->addInventoryThresholdFieldToCategory($schema);

        $this->addLowInventoryThresholdFieldToProduct($schema);
        $this->addLowInventoryThresholdFieldToCategory($schema);

        $this->addQuantityToOrderFieldsToProduct($schema);
        $this->addQuantityToOrderFieldsToCategory($schema);

        $this->addDecrementQuantityFieldToProduct($schema);
        $this->addDecrementQuantityFieldToCategory($schema);

        $this->addBackOrderFieldToProduct($schema);
        $this->addBackOrderFieldToCategory($schema);

        $this->addUpcomingFieldToProduct($schema);
        $this->addUpcomingFieldToCategory($schema);
        $this->addAvailabilityDateToProduct($schema);
        $this->addAvailabilityDateToCategory($schema);

        $this->updateWarehouseEntityRelations($schema);

        if (!$schema->hasTable('oro_inventory_level')
            && (
                $schema->hasTable('oro_warehouse_inventory_lev')
                || $schema->hasTable('orob2b_warehouse_inventory_lev')
            )
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

        $this->addUniqueIndex($schema);
    }

    private function renameTablesUpdateRelation(Schema $schema, QueryBag $queries): void
    {
        $fromTable = 'oro_warehouse_inventory_lev';
        $indexToDrop = 'uidx_oro_wh_wh_inventory_lev';
        if ($schema->hasTable('orob2b_warehouse_inventory_lev')) {
            $fromTable = 'orob2b_warehouse_inventory_lev';
            $indexToDrop = 'uidx_orob2b_wh_wh_inventory_lev';
        }

        //rename table
        $this->renameExtension->renameTable($schema, $queries, $fromTable, 'oro_inventory_level');

        $inventoryTable = $schema->getTable($fromTable);

        // drop warehouse indexes
        $inventoryTable->dropIndex($indexToDrop);

        // drop warehouse column
        $warehouseForeignKey = $this->getConstraintName($inventoryTable, 'warehouse_id');
        $inventoryTable->removeForeignKey($warehouseForeignKey);
        $inventoryTable->dropColumn('warehouse_id');

        $this->addEntityConfigUpdateQueries($queries);
    }

    private function updateWarehouseEntityRelations(Schema $schema): void
    {
        if (class_exists('Oro\Bundle\WarehouseBundle\Entity\Warehouse')) {
            return;
        }

        $table = $this->getWarehouseTable($schema);
        if (!$table) {
            return;
        }

        $notes = $schema->getTable('oro_note');
        if ($notes->hasColumn('warehouse_c913b87_id')) {
            $this->dropForeignKeyAndColumn($schema, 'oro_note', 'warehouse_c913b87_id');
        }
        if ($notes->hasColumn('warehouse_6eca7547_id')) {
            $this->dropForeignKeyAndColumn($schema, 'oro_note', 'warehouse_6eca7547_id');
        }

        if ($schema->getTable('oro_order')->hasColumn('warehouse_6eca7547_id')) {
            $this->dropForeignKeyAndColumn($schema, 'oro_order', 'warehouse_6eca7547_id');
        }
        if ($schema->getTable('oro_order')->hasColumn('warehouse_id')) {
            $this->dropForeignKeyAndColumn($schema, 'oro_order', 'warehouse_id');
        }
        if ($schema->getTable('oro_order_line_item')->hasColumn('warehouse_id')) {
            $this->dropForeignKeyAndColumn($schema, 'oro_order_line_item', 'warehouse_id');
        }

        $schema->dropTable($table);
    }

    private function getWarehouseTable(Schema $schema): ?string
    {
        $table = null;
        if ($schema->hasTable('oro_warehouse')) {
            $table = 'oro_warehouse';
        }
        if ($schema->hasTable('orob2b_warehouse')) {
            $table = 'orob2b_warehouse';
        }

        return $table;
    }

    private function dropForeignKeyAndColumn(Schema $schema, string $tableName, string $relationColumn): void
    {
        $table = $schema->getTable($tableName);
        $foreignKey = $this->getConstraintName($table, $relationColumn);
        $table->removeForeignKey($foreignKey);
        $table->dropColumn($relationColumn);
    }

    /**
     * Create oro_inventory_level table
     */
    private function createOroInventoryLevelTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_inventory_level');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('quantity', 'decimal', ['precision' => 20, 'scale' => 10]);
        $table->addColumn('product_id', 'integer');
        $table->addColumn('product_unit_precision_id', 'integer');
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add oro_inventory_level foreign keys.
     */
    private function addOroInventoryLevelForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_inventory_level');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product_unit_precision'),
            ['product_unit_precision_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }

    private function addEntityConfigUpdateQueries(QueryBag $queries): void
    {
        $configData = [
            'id' => 'oro.inventory.inventorylevel.id.label',
            'product' => 'oro.inventory.inventorylevel.product.label',
            'quantity' => 'oro.inventory.inventorylevel.quantity.label',
            'productUnitPrecision' => 'oro.inventory.inventorylevel.product_unit_precision.label',
            'warehouse' => 'oro.inventory.inventorylevel.warehouse.label',
        ];
        $this->addEntityFieldLabelConfigs($queries, 'Oro\Bundle\InventoryBundle\Entity\InventoryLevel', $configData);

        $configData = ['manageInventory' => 'oro.inventory.manage_inventory.label'];
        $this->addEntityFieldLabelConfigs($queries, 'Oro\Bundle\ProductBundle\Entity\Product', $configData);
        $this->addEntityFieldLabelConfigs($queries, 'Oro\Bundle\CatalogBundle\Entity\Category', $configData);

        $queries->addPostQuery(new UpdateEntityConfigEntityValueQuery(
            'Oro\Bundle\InventoryBundle\Entity\InventoryLevel',
            'entity',
            'label',
            'oro.inventory.inventorylevel.entity_label'
        ));

        $queries->addPostQuery(new UpdateEntityConfigEntityValueQuery(
            'Oro\Bundle\InventoryBundle\Entity\InventoryLevel',
            'entity',
            'plural_label',
            'oro.inventory.inventorylevel.entity_plural_label'
        ));

        $queries->addPostQuery(new UpdateEntityConfigExtendClassQuery(
            'Oro\Bundle\InventoryBundle\Entity\InventoryLevel',
            'Extend\Entity\EX_OroWarehouseBundle_WarehouseInventoryLevel',
            'Extend\Entity\EX_OroInventoryBundle_InventoryLevel'
        ));

        $queries->addPostQuery(new UpdateFallbackEntitySystemOptionConfig(
            'Oro\Bundle\ProductBundle\Entity\Product',
            'manageInventory',
            'oro_inventory.manage_inventory'
        ));
        $queries->addPostQuery(new UpdateFallbackEntitySystemOptionConfig(
            'Oro\Bundle\CatalogBundle\Entity\Category',
            'manageInventory',
            'oro_inventory.manage_inventory'
        ));
    }

    private function addEntityFieldLabelConfigs(QueryBag $queries, $class, $data): void
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

    private function addManageInventoryFieldToProduct(Schema $schema): void
    {
        if ($schema->getTable('oro_product')->hasColumn('manageinventory_id')) {
            return;
        }

        $this->addFallbackRelation(
            $schema,
            $this->extendExtension,
            'oro_product',
            'manageInventory',
            'oro.inventory.manage_inventory.label',
            [
                CategoryFallbackProvider::FALLBACK_ID => ['fieldName' => 'manageInventory'],
                SystemConfigFallbackProvider::FALLBACK_ID => ['configName' => 'oro_inventory.manage_inventory'],
            ],
            [
                'importexport' => ['full' => true],
                'security' => ['permissions' => 'VIEW;EDIT']
            ]
        );
    }

    private function addHighlightLowInventoryFieldToProduct(Schema $schema): void
    {
        if ($schema->getTable('oro_product')->hasColumn('highlightlowinventory_id')) {
            return;
        }

        $this->addFallbackRelation(
            $schema,
            $this->extendExtension,
            'oro_product',
            LowInventoryProvider::HIGHLIGHT_LOW_INVENTORY_OPTION,
            'oro.inventory.highlight_low_inventory.label',
            [
                CategoryFallbackProvider::FALLBACK_ID => [
                    'fieldName' => LowInventoryProvider::HIGHLIGHT_LOW_INVENTORY_OPTION
                ],
                SystemConfigFallbackProvider::FALLBACK_ID => ['configName' => 'oro_inventory.highlight_low_inventory'],
            ],
            [
                'importexport' => ['full' => true],
                'security' => ['permissions' => 'VIEW;EDIT']
            ]
        );
    }

    private function addManageInventoryFieldToCategory(Schema $schema): void
    {
        if ($schema->getTable('oro_catalog_category')->hasColumn('manageinventory_id')) {
            return;
        }

        $this->addFallbackRelation(
            $schema,
            $this->extendExtension,
            'oro_catalog_category',
            'manageInventory',
            'oro.inventory.manage_inventory.label',
            [
                ParentCategoryFallbackProvider::FALLBACK_ID => ['fieldName' => 'manageInventory'],
                SystemConfigFallbackProvider::FALLBACK_ID => ['configName' => 'oro_inventory.manage_inventory'],
            ]
        );
    }

    private function addHighlightLowInventoryFieldToCategory(Schema $schema): void
    {
        if ($schema->getTable('oro_catalog_category')->hasColumn('highlightlowinventory_id')) {
            return;
        }

        $this->addFallbackRelation(
            $schema,
            $this->extendExtension,
            'oro_catalog_category',
            LowInventoryProvider::HIGHLIGHT_LOW_INVENTORY_OPTION,
            'oro.inventory.highlight_low_inventory.label',
            [
                ParentCategoryFallbackProvider::FALLBACK_ID => [
                    'fieldName' => LowInventoryProvider::HIGHLIGHT_LOW_INVENTORY_OPTION
                ],
                SystemConfigFallbackProvider::FALLBACK_ID => ['configName' => 'oro_inventory.highlight_low_inventory'],
            ]
        );
    }

    private function addInventoryThresholdFieldToProduct(Schema $schema): void
    {
        if ($schema->getTable('oro_product')->hasColumn('inventoryThreshold_id')) {
            return;
        }

        $this->addFallbackRelation(
            $schema,
            $this->extendExtension,
            'oro_product',
            'inventoryThreshold',
            'oro.inventory.inventory_threshold.label',
            [
                CategoryFallbackProvider::FALLBACK_ID => ['fieldName' => 'inventoryThreshold'],
                SystemConfigFallbackProvider::FALLBACK_ID => [
                    'configName' => 'oro_inventory.inventory_threshold'
                ],
            ],
            ['importexport' => ['full' => true]]
        );
    }

    private function addLowInventoryThresholdFieldToProduct(Schema $schema): void
    {
        if ($schema->getTable('oro_product')->hasColumn('lowInventoryThreshold_id')) {
            return;
        }

        $this->addFallbackRelation(
            $schema,
            $this->extendExtension,
            'oro_product',
            LowInventoryProvider::LOW_INVENTORY_THRESHOLD_OPTION,
            'oro.inventory.low_inventory_threshold.label',
            [
                CategoryFallbackProvider::FALLBACK_ID => [
                    'fieldName' => LowInventoryProvider::LOW_INVENTORY_THRESHOLD_OPTION
                ],
                SystemConfigFallbackProvider::FALLBACK_ID => [
                    'configName' => 'oro_inventory.low_inventory_threshold'
                ],
            ],
            ['importexport' => ['full' => true]]
        );
    }

    private function addInventoryThresholdFieldToCategory(Schema $schema): void
    {
        if ($schema->getTable('oro_catalog_category')->hasColumn('inventoryThreshold_id')) {
            return;
        }

        $this->addFallbackRelation(
            $schema,
            $this->extendExtension,
            'oro_catalog_category',
            'inventoryThreshold',
            'oro.inventory.inventory_threshold.label',
            [
                ParentCategoryFallbackProvider::FALLBACK_ID => ['fieldName' => 'inventoryThreshold'],
                SystemConfigFallbackProvider::FALLBACK_ID => [
                    'configName' => 'oro_inventory.inventory_threshold'
                ],
            ]
        );
    }

    private function addLowInventoryThresholdFieldToCategory(Schema $schema): void
    {
        if ($schema->getTable('oro_catalog_category')->hasColumn('lowInventoryThreshold_id')) {
            return;
        }

        $this->addFallbackRelation(
            $schema,
            $this->extendExtension,
            'oro_catalog_category',
            LowInventoryProvider::LOW_INVENTORY_THRESHOLD_OPTION,
            'oro.inventory.low_inventory_threshold.label',
            [
                ParentCategoryFallbackProvider::FALLBACK_ID => [
                    'fieldName' => LowInventoryProvider::LOW_INVENTORY_THRESHOLD_OPTION
                ],
                SystemConfigFallbackProvider::FALLBACK_ID => [
                    'configName' => 'oro_inventory.low_inventory_threshold'
                ],
            ]
        );
    }

    private function addQuantityToOrderFieldsToProduct(Schema $schema): void
    {
        $this->addFallbackRelation(
            $schema,
            $this->extendExtension,
            'oro_product',
            Inventory::FIELD_MINIMUM_QUANTITY_TO_ORDER,
            'oro.inventory.fields.product.minimum_quantity_to_order.label',
            [
                CategoryFallbackProvider::FALLBACK_ID => [
                    'fieldName' => Inventory::FIELD_MINIMUM_QUANTITY_TO_ORDER,
                ],
                SystemConfigFallbackProvider::FALLBACK_ID => [
                    'configName' => 'oro_inventory.minimum_quantity_to_order',
                ],
            ],
            ['importexport' => ['full' => true]]
        );

        $this->addFallbackRelation(
            $schema,
            $this->extendExtension,
            'oro_product',
            Inventory::FIELD_MAXIMUM_QUANTITY_TO_ORDER,
            'oro.inventory.fields.product.maximum_quantity_to_order.label',
            [
                CategoryFallbackProvider::FALLBACK_ID => [
                    'fieldName' => Inventory::FIELD_MAXIMUM_QUANTITY_TO_ORDER,
                ],
                SystemConfigFallbackProvider::FALLBACK_ID => [
                    'configName' => 'oro_inventory.maximum_quantity_to_order',
                ],
            ],
            ['importexport' => ['full' => true]]
        );
    }

    private function addQuantityToOrderFieldsToCategory(Schema $schema): void
    {
        $this->addFallbackRelation(
            $schema,
            $this->extendExtension,
            'oro_catalog_category',
            Inventory::FIELD_MINIMUM_QUANTITY_TO_ORDER,
            'oro.inventory.fields.category.minimum_quantity_to_order.label',
            [
                SystemConfigFallbackProvider::FALLBACK_ID => [
                    'configName' => 'oro_inventory.minimum_quantity_to_order',
                ],
            ]
        );
        $this->addFallbackRelation(
            $schema,
            $this->extendExtension,
            'oro_catalog_category',
            Inventory::FIELD_MAXIMUM_QUANTITY_TO_ORDER,
            'oro.inventory.fields.category.maximum_quantity_to_order.label',
            [
                SystemConfigFallbackProvider::FALLBACK_ID => [
                    'configName' => 'oro_inventory.maximum_quantity_to_order',
                ],
            ]
        );
    }

    private function addDecrementQuantityFieldToProduct(Schema $schema): void
    {
        if ($schema->getTable('oro_product')->hasColumn('decrementQuantity_id')) {
            return;
        }

        $this->addFallbackRelation(
            $schema,
            $this->extendExtension,
            'oro_product',
            'decrementQuantity',
            'oro.inventory.decrement_inventory.label',
            [
                CategoryFallbackProvider::FALLBACK_ID => ['fieldName' => 'decrementQuantity'],
                SystemConfigFallbackProvider::FALLBACK_ID => ['configName' => 'oro_inventory.decrement_inventory'],
            ],
            ['importexport' => ['full' => true]]
        );
    }

    private function addDecrementQuantityFieldToCategory(Schema $schema): void
    {
        if ($schema->getTable('oro_catalog_category')->hasColumn('decrementQuantity_id')) {
            return;
        }

        $this->addFallbackRelation(
            $schema,
            $this->extendExtension,
            'oro_catalog_category',
            'decrementQuantity',
            'oro.inventory.decrement_inventory.label',
            [
                ParentCategoryFallbackProvider::FALLBACK_ID => ['fieldName' => 'decrementQuantity'],
                SystemConfigFallbackProvider::FALLBACK_ID => ['configName' => 'oro_inventory.decrement_inventory'],
            ]
        );
    }

    private function addBackOrderFieldToProduct(Schema $schema): void
    {
        if ($schema->getTable('oro_product')->hasColumn('backOrder_id')) {
            return;
        }

        $this->addFallbackRelation(
            $schema,
            $this->extendExtension,
            'oro_product',
            'backOrder',
            'oro.inventory.backorders.label',
            [
                CategoryFallbackProvider::FALLBACK_ID => ['fieldName' => 'backOrder'],
                SystemConfigFallbackProvider::FALLBACK_ID => ['configName' => 'oro_inventory.backorders'],
            ],
            [
                'importexport' => ['full' => true],
                'security' => ['permissions' => 'VIEW;EDIT']
            ]
        );
    }

    private function addBackOrderFieldToCategory(Schema $schema): void
    {
        if ($schema->getTable('oro_catalog_category')->hasColumn('backOrder_id')) {
            return;
        }

        $this->addFallbackRelation(
            $schema,
            $this->extendExtension,
            'oro_catalog_category',
            'backOrder',
            'oro.inventory.backorders.label',
            [
                ParentCategoryFallbackProvider::FALLBACK_ID => ['fieldName' => 'backOrder'],
                SystemConfigFallbackProvider::FALLBACK_ID => ['configName' => 'oro_inventory.backorders'],
            ]
        );
    }

    private function addUpcomingFieldToProduct(Schema $schema): void
    {
        $this->addFallbackRelation(
            $schema,
            $this->extendExtension,
            'oro_product',
            UpcomingProductProvider::IS_UPCOMING,
            'oro.inventory.is_upcoming.label',
            [
                CategoryFallbackProvider::FALLBACK_ID => ['fieldName' => UpcomingProductProvider::IS_UPCOMING],
            ],
            [
                'importexport' => ['full' => true],
                'fallback' => ['fallbackType' => EntityFallbackResolver::TYPE_BOOLEAN],
                'security' => ['permissions' => 'VIEW;EDIT']
            ]
        );
    }

    private function addUpcomingFieldToCategory(Schema $schema): void
    {
        $this->addFallbackRelation(
            $schema,
            $this->extendExtension,
            'oro_catalog_category',
            UpcomingProductProvider::IS_UPCOMING,
            'oro.inventory.is_upcoming.label',
            [
                ParentCategoryFallbackProvider::FALLBACK_ID => ['fieldName' => UpcomingProductProvider::IS_UPCOMING],
            ],
            [
                'fallback' => ['fallbackType' => EntityFallbackResolver::TYPE_BOOLEAN]
            ]
        );
    }

    private function addAvailabilityDateToProduct(Schema $schema): void
    {
        $table = $schema->getTable('oro_product');
        $table->addColumn(
            'availability_date',
            'datetime',
            [
                'notnull' => false,
                'comment' => '(DC2Type:datetime)',
                OroOptions::KEY => [
                    'entity' => ['label' => 'oro.inventory.availability_date.label'],
                    'extend' => [
                        'owner' => ExtendScope::OWNER_CUSTOM,
                        'is_extend' => true,
                    ],
                    'datagrid' => ['is_visible' => DatagridScope::IS_VISIBLE_FALSE],
                    'form' => ['is_enabled' => false,],
                    'view' => ['is_displayable' => false],
                    'merge' => ['display' => false],
                    'dataaudit' => ['auditable' => true],
                    'importexport' => ['full' => true]
                ],
            ]
        );
    }

    private function addAvailabilityDateToCategory(Schema $schema): void
    {
        $table = $schema->getTable('oro_catalog_category');
        $table->addColumn(
            'availability_date',
            'datetime',
            [
                'notnull' => false,
                'comment' => '(DC2Type:datetime)',
                OroOptions::KEY => [
                    'entity' => ['label' => 'oro.inventory.availability_date.label'],
                    'extend' => [
                        'owner' => ExtendScope::OWNER_CUSTOM,
                        'is_extend' => true,
                    ],
                    'datagrid' => ['is_visible' => DatagridScope::IS_VISIBLE_FALSE],
                    'form' => ['is_enabled' => false,],
                    'view' => ['is_displayable' => false],
                    'merge' => ['display' => false],
                    'dataaudit' => ['auditable' => true],
                    'importexport' => ['excluded' => true]
                ],
            ]
        );
    }

    private function addUniqueIndex(Schema $schema): void
    {
        $table = $schema->getTable('oro_inventory_level');
        $indexName = 'oro_inventory_level_unique_index';

        if (!$table->hasIndex($indexName)) {
            $table->addUniqueIndex(
                ["product_id", "product_unit_precision_id", "organization_id"],
                $indexName
            );
        }
    }
}
