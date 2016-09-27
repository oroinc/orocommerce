<?php

namespace Oro\Bundle\WarehouseBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\CatalogBundle\Fallback\Provider\CategoryFallbackProvider;
use Oro\Bundle\CatalogBundle\Fallback\Provider\ParentCategoryFallbackProvider;
use Oro\Bundle\EntityBundle\Fallback\Provider\SystemConfigFallbackProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddManageInventoryFields implements Migration, ExtendExtensionAwareInterface
{
    /** @var ExtendExtension */
    protected $extendExtension;

    /**
     * @param ExtendExtension $extendExtension
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addManageInventoryFieldToCategory($schema);
        $this->addManageInventoryFieldToProduct($schema);
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
                'entity' => [
                    'label' => 'oro.warehouse.manage_inventory.label',
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
                        CategoryFallbackProvider::FALLBACK_ID => ['fieldName' => 'manageInventory'],
                        SystemConfigFallbackProvider::FALLBACK_ID => ['configName' => 'oro_warehouse.manage_inventory'],
                    ],
                ],
            ]
        );
    }

    /**
     * @param Schema $schema
     */
    protected function addManageInventoryFieldToCategory(Schema $schema)
    {
        $categoryTable = $schema->getTable('oro_catalog_category');
        $fallbackTable = $schema->getTable('oro_entity_fallback_value');
        $this->extendExtension->addManyToOneRelation(
            $schema,
            $categoryTable,
            'manageInventory',
            $fallbackTable,
            'id',
            [
                'entity' => [
                    'label' => 'oro.warehouse.manage_inventory.label',
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
                        ParentCategoryFallbackProvider::FALLBACK_ID => ['fieldName' => 'manageInventory'],
                        SystemConfigFallbackProvider::FALLBACK_ID => ['configName' => 'oro_warehouse.manage_inventory'],
                    ],
                ],
            ]
        );
    }
}
