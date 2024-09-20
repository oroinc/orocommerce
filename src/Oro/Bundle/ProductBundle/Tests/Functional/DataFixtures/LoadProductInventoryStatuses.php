<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\InitialFixtureInterface;

/**
 * Loads product inventory statuses from the database.
 */
class LoadProductInventoryStatuses extends AbstractFixture implements InitialFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $repository = $manager->getRepository(EnumOption::class);
        $this->addReference(
            'in_stock',
            $repository->findOneBy(
                ['id' => ExtendHelper::buildEnumOptionId('prod_inventory_status', 'in_stock')]
            )
        );
        $this->addReference(
            'out_of_stock',
            $repository->findOneBy(
                ['id' => ExtendHelper::buildEnumOptionId('prod_inventory_status', 'out_of_stock')]
            )
        );
        $this->addReference(
            'discontinued',
            $repository->findOneBy(
                ['id' => ExtendHelper::buildEnumOptionId('prod_inventory_status', 'discontinued')]
            )
        );
    }
}
