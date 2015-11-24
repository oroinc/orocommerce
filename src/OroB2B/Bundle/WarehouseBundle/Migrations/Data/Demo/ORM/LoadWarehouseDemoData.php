<?php

namespace OroB2B\Bundle\WarehouseBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;

use OroB2B\Bundle\WarehouseBundle\Entity\Warehouse;

class LoadWarehouseDemoData implements FixtureInterface
{
    use UserUtilityTrait;

    /**
     * @inheritDoc
     */
    public function load(ObjectManager $manager)
    {
        /** @var EntityManager $manager */
        $user = $this->getFirstUser($manager);
        $businessUnit = $user->getOwner();
        $organization = $user->getOrganization();

        $warehouse = new Warehouse();
        $warehouse
            ->setName('Warehouse 1')
            ->setOwner($businessUnit)
            ->setOrganization($organization)
        ;

        $manager->persist($warehouse);
        $manager->flush();
    }
}
