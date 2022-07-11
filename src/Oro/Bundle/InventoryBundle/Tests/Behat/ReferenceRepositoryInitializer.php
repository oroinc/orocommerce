<?php

namespace Oro\Bundle\InventoryBundle\Tests\Behat;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\ReferenceRepositoryInitializerInterface;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\Collection;

class ReferenceRepositoryInitializer implements ReferenceRepositoryInitializerInterface
{
    /**
     * {@inheritdoc}
     */
    public function init(ManagerRegistry $doctrine, Collection $referenceRepository): void
    {
        $inventoryStatusClassName = ExtendHelper::buildEnumValueClassName('prod_inventory_status');
        /** @var AbstractEnumValue[] $enumInventoryStatuses */
        $enumInventoryStatuses = $doctrine->getManagerForClass($inventoryStatusClassName)
            ->getRepository($inventoryStatusClassName)
            ->findAll();

        $inventoryStatuses = [];
        foreach ($enumInventoryStatuses as $inventoryStatus) {
            $inventoryStatuses[$inventoryStatus->getId()] = $inventoryStatus;
        }

        $referenceRepository->set(
            'enumInventoryStatuses',
            $inventoryStatuses[Product::INVENTORY_STATUS_IN_STOCK]
        );

        $referenceRepository->set(
            'enumInventoryStatusOutOfStock',
            $inventoryStatuses[Product::INVENTORY_STATUS_OUT_OF_STOCK]
        );
    }
}
