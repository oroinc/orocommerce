<?php

namespace Oro\Bundle\InventoryBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\CatalogBundle\Fallback\Provider\CategoryFallbackProvider;
use Oro\Bundle\CatalogBundle\Fallback\Provider\ParentCategoryFallbackProvider;
use Oro\Bundle\EntityBundle\Fallback\Provider\SystemConfigFallbackProvider;
use Oro\Bundle\EntityBundle\Migration\AddFallbackRelationTrait;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroInventoryBundle implements Installation, ExtendExtensionAwareInterface
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
     * @inheritDoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addInventoryThresholdFieldToProduct($schema);
        $this->addInventoryThresholdFieldToCategory($schema);

        $this->addDecrementQuantityFieldToProduct($schema);
        $this->addDecrementQuantityFieldToCategory($schema);

        $this->addBackOrderFieldToProduct($schema);
        $this->addBackOrderFieldToCategory($schema);
    }

    public function addInventoryThresholdFieldToProduct(Schema $schema)
    {
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
            ]
        );
    }

    public function addInventoryThresholdFieldToCategory(Schema $schema)
    {
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

    protected function addDecrementQuantityFieldToProduct(Schema $schema)
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
            ]
        );
    }

    public function addDecrementQuantityFieldToCategory(Schema $schema)
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

    protected function addBackOrderFieldToProduct(Schema $schema)
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
            ]
        );
    }

    public function addBackOrderFieldToCategory(Schema $schema)
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

    /**
     * @inheritDoc
     */
    public function getMigrationVersion()
    {
        return 'v1_1';
    }
}
