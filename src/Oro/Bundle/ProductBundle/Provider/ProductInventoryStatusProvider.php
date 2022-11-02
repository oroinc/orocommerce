<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

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
        $inventoryStatusClassName = ExtendHelper::buildEnumValueClassName('prod_inventory_status');
        /** @var AbstractEnumValue[] $inventoryStatuses */
        $inventoryStatuses = $this->doctrine->getRepository($inventoryStatusClassName)->findAll();

        $inventoryStatusesList = [];
        foreach ($inventoryStatuses as $inventoryStatus) {
            $code = $inventoryStatus->getId();
            $inventoryStatusesList[$code] = $inventoryStatus->getName();
        }

        return $inventoryStatusesList;
    }
}
