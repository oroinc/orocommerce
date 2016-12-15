<?php

namespace Oro\Bundle\InventoryBundle\Validator;

use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;

class InventoryLevelOrderValidator
{
    /**
     * @var EntityFallbackResolver
     */
    protected $fallbackResolver;

    /**
     * @param EntityFallbackResolver $fallbackResolver
     */
    public function __construct(EntityFallbackResolver $fallbackResolver)
    {
        $this->fallbackResolver = $fallbackResolver;
    }

    /**
     * @param InventoryLevel $inventoryLevel
     * @param float $quantityToOrder
     * @return bool
     */
    public function hasEnoughQuantity(InventoryLevel $inventoryLevel, $quantityToOrder)
    {
        $product = $inventoryLevel->getProduct();
        if (!$this->fallbackResolver->getFallbackValue($product, 'decrementQuantity')
            || $this->fallbackResolver->getFallbackValue($product, 'backOrder')
        ) {
            return true;
        }

        $initialQuantity = $inventoryLevel->getQuantity();
        $inventoryThreshold = $this->fallbackResolver->getFallbackValue($product, 'inventoryThreshold');
        if (($initialQuantity - $inventoryThreshold) < $quantityToOrder) {
            return false;
        }

        return true;
    }
}
