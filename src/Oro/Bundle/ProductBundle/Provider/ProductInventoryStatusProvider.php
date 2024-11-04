<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Provides available inventory status codes and labels.
 */
class ProductInventoryStatusProvider
{
    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var array */
    private $inventoryStatuses;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @return array
     */
    public function getAvailableProductInventoryStatuses()
    {
        if ($this->inventoryStatuses === null) {
            $this->inventoryStatuses = $this->getInventoryStatusesList();
        }

        return $this->inventoryStatuses;
    }

    private function getInventoryStatusesList(): array
    {
        /** @var EnumOptionInterface[] $inventoryStatuses */
        $inventoryStatuses = $this->doctrine->getRepository(EnumOption::class)
            ->findBy(['enumCode' => Product::INVENTORY_STATUS_ENUM_CODE]);

        $inventoryStatusesList = [];
        foreach ($inventoryStatuses as $inventoryStatus) {
            $inventoryStatusesList[$inventoryStatus->getId()] = $inventoryStatus->getName();
        }

        return $inventoryStatusesList;
    }
}
