<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Stub;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\ProductBundle\Entity\Product;

class ProductWithInventoryStatus extends Product
{
    /** @var AbstractEnumValue */
    private $inventoryStatus;

    public function getInventoryStatus(): AbstractEnumValue
    {
        return $this->inventoryStatus;
    }

    public function setInventoryStatus(AbstractEnumValue $inventoryStatus): void
    {
        $this->inventoryStatus = $inventoryStatus;
    }
}
