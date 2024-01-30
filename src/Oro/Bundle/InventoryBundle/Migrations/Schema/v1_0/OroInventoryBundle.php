<?php

namespace Oro\Bundle\InventoryBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareTrait;
use Oro\Bundle\CatalogBundle\Fallback\Provider\CategoryFallbackProvider;
use Oro\Bundle\CatalogBundle\Fallback\Provider\ParentCategoryFallbackProvider;
use Oro\Bundle\ConfigBundle\Migration\RenameConfigSectionQuery;
use Oro\Bundle\EntityBundle\Fallback\Provider\SystemConfigFallbackProvider;
use Oro\Bundle\EntityBundle\Migration\AddFallbackRelationTrait;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigEntityValueQuery;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\MigrationConstraintTrait;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroInventoryBundle implements
    Installation,
    ExtendExtensionAwareInterface,
    RenameExtensionAwareInterface,
    ActivityExtensionAwareInterface
{
    use MigrationConstraintTrait;
    use ExtendExtensionAwareTrait;
    use RenameExtensionAwareTrait;
    use AddFallbackRelationTrait;
    use ActivityExtensionAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function getMigrationVersion(): string
    {
        return 'v1_0';
    }

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->addManageInventoryFieldToProduct($schema);
        $this->addManageInventoryFieldToCategory($schema);

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

        $this->removeWarehouseNotesAssociation($schema, $table);

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

    private function removeWarehouseNotesAssociation(Schema $schema, string $table): void
    {
        $notes = $schema->getTable('oro_note');
        if ($notes->hasColumn('warehouse_c913b87_id')) {
            $this->dropForeignKeyAndColumn($schema, 'oro_note', 'warehouse_c913b87_id');
        }
        if ($notes->hasColumn('warehouse_6eca7547_id')) {
            $this->dropForeignKeyAndColumn($schema, 'oro_note', 'warehouse_6eca7547_id');
        }
        $associationTableName = $this->activityExtension->getAssociationTableName('oro_note', $table);
        if ($associationTableName) {
            $schema->dropTable($associationTableName);
        }
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
    }

    private function addManageInventoryFieldToProduct(Schema $schema): void
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

    private function addManageInventoryFieldToCategory(Schema $schema): void
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

    private function addEntityFieldLabelConfigs(QueryBag $queries, string $class, array $data): void
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
