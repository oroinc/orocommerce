<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Api\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\InitialFixtureInterface;

/**
 * Loads product units from the database.
 */
class LoadProductUnits extends AbstractFixture implements InitialFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $repository = $manager->getRepository(ProductUnit::class);
        $this->addReference('item', $repository->findOneBy(['code' => 'item']));
        $this->addReference('set', $repository->findOneBy(['code' => 'set']));
        $this->addReference('piece', $repository->findOneBy(['code' => 'piece']));
    }
}
