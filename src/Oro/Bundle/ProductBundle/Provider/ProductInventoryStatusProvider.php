<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class ProductInventoryStatusProvider
{
    /**
     * @var  ManagerRegistry
     */
    protected $doctrine;

    /**
     * @param ManagerRegistry $doctrine
     */
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @return array
     */
    public function getAvailableProductInventoryStatuses()
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
