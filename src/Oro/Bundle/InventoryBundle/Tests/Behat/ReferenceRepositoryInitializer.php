<?php

namespace Oro\Bundle\InventoryBundle\Tests\Behat;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Nelmio\Alice\Instances\Collection as AliceCollection;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\ReferenceRepositoryInitializerInterface;

class ReferenceRepositoryInitializer implements ReferenceRepositoryInitializerInterface
{
    /**
     * {@inheritdoc}
     */
    public function init(Registry $doctrine, AliceCollection $referenceRepository)
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
