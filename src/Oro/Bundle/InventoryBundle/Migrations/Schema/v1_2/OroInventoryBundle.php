<?php

namespace Oro\Bundle\InventoryBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\CatalogBundle\Fallback\Provider\CategoryFallbackProvider;
use Oro\Bundle\CatalogBundle\Fallback\Provider\ParentCategoryFallbackProvider;
use Oro\Bundle\EntityBundle\Fallback\Provider\SystemConfigFallbackProvider;
use Oro\Bundle\EntityBundle\Migration\AddFallbackRelationTrait;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroInventoryBundle implements Migration, ExtendExtensionAwareInterface
{
    use AddFallbackRelationTrait;

    /** @var ExtendExtension */
    protected $extendExtension;

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
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addManageLowInventoryFieldToProduct($schema);
        $this->addManageLowInventoryFieldToCategory($schema);
        $this->addLowInventoryThresholdFieldToProduct($schema);
        $this->addLowInventoryThresholdFieldToCategory($schema);
    }

    /**
     * @param Schema $schema
     */
    protected function addManageLowInventoryFieldToProduct(Schema $schema)
    {
        if ($schema->getTable('oro_product')->hasColumn('managelowinventory_id')) {
            return;
        }

        $this->addFallbackRelation(
            $schema,
            $this->extendExtension,
            'oro_product',
            'manageLowInventory',
            'oro.inventory.manage_low_inventory.label',
            [
                CategoryFallbackProvider::FALLBACK_ID => ['fieldName' => 'manageLowInventory'],
                SystemConfigFallbackProvider::FALLBACK_ID => ['configName' => 'oro_inventory.manage_low_inventory'],
            ]
        );
    }

    /**
     * @param Schema $schema
     */
    protected function addLowInventoryThresholdFieldToProduct(Schema $schema)
    {
        if ($schema->getTable('oro_product')->hasColumn('lowInventoryThreshold_id')) {
            return;
        }

        $this->addFallbackRelation(
            $schema,
            $this->extendExtension,
            'oro_product',
            'lowInventoryThreshold',
            'oro.inventory.low_inventory_threshold.label',
            [
                CategoryFallbackProvider::FALLBACK_ID => ['fieldName' => 'lowInventoryThreshold'],
                SystemConfigFallbackProvider::FALLBACK_ID => [
                    'configName' => 'oro_inventory.low_inventory_threshold'
                ],
            ]
        );
    }

    /**
     * @param Schema $schema
     */
    public function addLowInventoryThresholdFieldToCategory(Schema $schema)
    {
        if ($schema->getTable('oro_catalog_category')->hasColumn('inventoryLowThreshold_id')) {
            return;
        }

        $this->addFallbackRelation(
            $schema,
            $this->extendExtension,
            'oro_catalog_category',
            'inventoryLowThreshold',
            'oro.inventory.inventory_low_threshold.label',
            [
                ParentCategoryFallbackProvider::FALLBACK_ID => ['fieldName' => 'inventoryLowThreshold'],
                SystemConfigFallbackProvider::FALLBACK_ID => [
                    'configName' => 'oro_inventory.inventory_low_threshold'
                ],
            ]
        );
    }

    /**
     * @param Schema $schema
     */
    protected function addManageLowInventoryFieldToCategory(Schema $schema)
    {
        if ($schema->getTable('oro_catalog_category')->hasColumn('managelowinventory_id')) {
            return;
        }

        $this->addFallbackRelation(
            $schema,
            $this->extendExtension,
            'oro_catalog_category',
            'manageLowInventory',
            'oro.inventory.manage_low_inventory.label',
            [
                ParentCategoryFallbackProvider::FALLBACK_ID => ['fieldName' => 'manageLowInventory'],
                SystemConfigFallbackProvider::FALLBACK_ID => ['configName' => 'oro_inventory.manage_low_inventory'],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_2';
    }
}
