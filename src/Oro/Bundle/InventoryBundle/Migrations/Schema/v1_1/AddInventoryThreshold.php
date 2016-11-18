<?php
namespace Oro\Bundle\InventoryBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\CatalogBundle\Fallback\Provider\CategoryFallbackProvider;
use Oro\Bundle\CatalogBundle\Fallback\Provider\ParentCategoryFallbackProvider;
use Oro\Bundle\EntityBundle\Fallback\Provider\SystemConfigFallbackProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddInventoryThreshold implements Installation, ExtendExtensionAwareInterface
{
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
     * @inheritDoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addInventoryThresholdFieldToProduct($schema);
        $this->addInventoryThresholdFieldToCategory($schema);
    }

    /**
     * @param Schema $schema
     */
    public function addInventoryThresholdFieldToProduct(Schema $schema)
    {
        $productTable = $schema->getTable('oro_product');
        $fallbackTable = $schema->getTable('oro_entity_fallback_value');
        $this->extendExtension->addManyToOneRelation(
            $schema,
            $productTable,
            'inventoryThreshold',
            $fallbackTable,
            'id',
            [
                'entity' => [
                    'label' => 'oro.inventory.inventory_threshold.label',
                ],
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
                        CategoryFallbackProvider::FALLBACK_ID => ['fieldName' => 'inventoryThreshold'],
                        SystemConfigFallbackProvider::FALLBACK_ID => [
                            'configName' => 'oro_inventory.inventory_threshold'
                        ],
                    ],
                ],
            ]
        );
    }

    /**
     * @param Schema $schema
     */
    public function addInventoryThresholdFieldToCategory(Schema $schema)
    {
        $productTable = $schema->getTable('oro_catalog_category');
        $fallbackTable = $schema->getTable('oro_entity_fallback_value');
        $this->extendExtension->addManyToOneRelation(
            $schema,
            $productTable,
            'inventoryThreshold',
            $fallbackTable,
            'id',
            [
                'entity' => [
                    'label' => 'oro.inventory.inventory_threshold.label',
                ],
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
                        ParentCategoryFallbackProvider::FALLBACK_ID => ['fieldName' => 'inventoryThreshold'],
                        SystemConfigFallbackProvider::FALLBACK_ID => [
                            'configName' => 'oro_inventory.inventory_threshold'
                        ],
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
        return 'v1_1';
    }
}
