<?php

namespace Oro\Bundle\InventoryBundle\Inventory;

use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\ProductBundle\Entity\Product;

class InventoryStatusHandler
{
    /**
     * @var EntityFallbackResolver
     */
    protected $entityFallbackResolver;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @param EntityFallbackResolver $entityFallbackResolver
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(EntityFallbackResolver $entityFallbackResolver, DoctrineHelper $doctrineHelper)
    {
        $this->entityFallbackResolver = $entityFallbackResolver;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param InventoryLevel $inventoryLevel
     */
    public function changeInventoryStatusWhenDecrement(InventoryLevel $inventoryLevel)
    {
        $product = $inventoryLevel->getProduct();
        $inventoryThreshold = $this->entityFallbackResolver->getFallbackValue($product, 'inventoryThreshold');
        if ($inventoryLevel->getQuantity() <= $inventoryThreshold) {
            $this->setInventoryStatusForDecrement($product);
        }
    }

    /**
     * @param Product $product
     */
    protected function setInventoryStatusForDecrement(Product $product)
    {
        $inventoryStatusEntityName = ExtendHelper::buildEnumValueClassName('prod_inventory_status');
        $status = $this->doctrineHelper->getEntityRepository($inventoryStatusEntityName)->findOneById(
            Product::INVENTORY_STATUS_OUT_OF_STOCK
        );
        $product->setInventoryStatus($status);
    }
}
