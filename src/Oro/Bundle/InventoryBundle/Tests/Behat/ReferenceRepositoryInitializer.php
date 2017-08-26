<?php

namespace Oro\Bundle\InventoryBundle\Tests\Behat;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Nelmio\Alice\Instances\Collection as AliceCollection;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\ReferenceRepositoryInitializerInterface;

class ReferenceRepositoryInitializer implements ReferenceRepositoryInitializerInterface
{
    /**
     * {@inheritdoc}
     */
    public function init(Registry $doctrine, AliceCollection $referenceRepository)
    {
        $inventoryStatusClassName = ExtendHelper::buildEnumValueClassName('prod_inventory_status');
        $enumInventoryStatuses = $doctrine->getManager()
            ->getRepository($inventoryStatusClassName)
            ->findOneBy(['id' => 'in_stock']);
        $referenceRepository->set('enumInventoryStatuses', $enumInventoryStatuses);
    }
}
