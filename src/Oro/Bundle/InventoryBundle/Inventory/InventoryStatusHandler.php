<?php

namespace Oro\Bundle\InventoryBundle\Inventory;

use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Set 'Out Of Stock' Inventory Status for product on q-ty decrement
 */
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

    public function __construct(EntityFallbackResolver $entityFallbackResolver, DoctrineHelper $doctrineHelper)
    {
        $this->entityFallbackResolver = $entityFallbackResolver;
        $this->doctrineHelper = $doctrineHelper;
    }

    public function changeInventoryStatusWhenDecrement(InventoryLevel $inventoryLevel)
    {
        $product = $inventoryLevel->getProduct();
        $inventoryThreshold = $this->entityFallbackResolver->getFallbackValue($product, 'inventoryThreshold');
        if ($inventoryLevel->getQuantity() <= $inventoryThreshold) {
            $this->setInventoryStatusForDecrement($product);
        }
    }

    protected function setInventoryStatusForDecrement(Product $product)
    {
        $status = $this->doctrineHelper->getEntityRepository(EnumOption::class)->findOneBy(
            [
                'id' => ExtendHelper::buildEnumOptionId(
                    Product::INVENTORY_STATUS_ENUM_CODE,
                    Product::INVENTORY_STATUS_OUT_OF_STOCK
                )
            ]
        );
        $product->setInventoryStatus($status);
    }
}
