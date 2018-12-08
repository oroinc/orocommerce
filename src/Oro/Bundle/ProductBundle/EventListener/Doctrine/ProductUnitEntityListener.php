<?php

namespace Oro\Bundle\ProductBundle\EventListener\Doctrine;

use Oro\Bundle\ProductBundle\Provider\ProductUnitsProvider;

/**
 * Clears the product units cache on any doctrine event related to ProductUnit entity.
 */
class ProductUnitEntityListener
{
    /** @var ProductUnitsProvider */
    private $productUnitsProvider;

    /**
     * @param ProductUnitsProvider $productUnitsProvider
     */
    public function __construct(ProductUnitsProvider $productUnitsProvider)
    {
        $this->productUnitsProvider = $productUnitsProvider;
    }

    public function invalidateProductUnitCache(): void
    {
        $this->productUnitsProvider->clearCache();
    }
}
