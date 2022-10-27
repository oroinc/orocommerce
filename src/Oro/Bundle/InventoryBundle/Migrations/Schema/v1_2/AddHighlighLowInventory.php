<?php

namespace Oro\Bundle\InventoryBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\CatalogBundle\Fallback\Provider\CategoryFallbackProvider;
use Oro\Bundle\CatalogBundle\Fallback\Provider\ParentCategoryFallbackProvider;
use Oro\Bundle\EntityBundle\Fallback\Provider\SystemConfigFallbackProvider;
use Oro\Bundle\EntityBundle\Migration\AddFallbackRelationTrait;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\InventoryBundle\Inventory\LowInventoryProvider;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddHighlighLowInventory implements Migration, ExtendExtensionAwareInterface
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
        $this->addHighlightLowInventoryFieldToProduct($schema);
        $this->addHighlightLowInventoryFieldToCategory($schema);
        $this->addLowInventoryThresholdFieldToProduct($schema);
        $this->addLowInventoryThresholdFieldToCategory($schema);
    }

    protected function addHighlightLowInventoryFieldToProduct(Schema $schema)
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
            ]
        );
    }

    protected function addLowInventoryThresholdFieldToProduct(Schema $schema)
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
            ]
        );
    }

    public function addLowInventoryThresholdFieldToCategory(Schema $schema)
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

    protected function addHighlightLowInventoryFieldToCategory(Schema $schema)
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

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_2';
    }
}
