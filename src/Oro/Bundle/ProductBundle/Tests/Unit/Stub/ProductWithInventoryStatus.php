<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Stub;

use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;
use Oro\Bundle\ProductBundle\Entity\Product;

class ProductWithInventoryStatus extends Product
{
    /** @var EnumOptionInterface */
    private $inventoryStatus;

    public function getInventoryStatus(): EnumOptionInterface
    {
        return $this->inventoryStatus;
    }

    public function setInventoryStatus(EnumOptionInterface $inventoryStatus): void
    {
        $this->inventoryStatus = $inventoryStatus;
    }
}
