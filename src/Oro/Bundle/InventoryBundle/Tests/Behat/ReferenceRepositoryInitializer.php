<?php

namespace Oro\Bundle\InventoryBundle\Tests\Behat;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\ReferenceRepositoryInitializerInterface;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\Collection;

class ReferenceRepositoryInitializer implements ReferenceRepositoryInitializerInterface
{
    #[\Override]
    public function init(ManagerRegistry $doctrine, Collection $referenceRepository): void
    {
        /** @var EnumOptionInterface[] $enumInventoryStatuses */
        $enumInventoryStatuses = $doctrine->getManagerForClass(EnumOption::class)
            ->getRepository(EnumOption::class)
            ->findBy(['enumCode' => Product::INVENTORY_STATUS_ENUM_CODE]);

        $inventoryStatuses = [];
        foreach ($enumInventoryStatuses as $inventoryStatus) {
            $inventoryStatuses[$inventoryStatus->getInternalId()] = $inventoryStatus;
        }

        $referenceRepository->set(
            'enumInventoryStatuses',
            $inventoryStatuses[Product::INVENTORY_STATUS_IN_STOCK]
        );

        $referenceRepository->set(
            'inStock',
            $inventoryStatuses[Product::INVENTORY_STATUS_IN_STOCK]
        );

        $referenceRepository->set(
            'enumInventoryStatusOutOfStock',
            $inventoryStatuses[Product::INVENTORY_STATUS_OUT_OF_STOCK]
        );

        $referenceRepository->set(
            'outOfStock',
            $inventoryStatuses[Product::INVENTORY_STATUS_IN_STOCK]
        );
    }
}
