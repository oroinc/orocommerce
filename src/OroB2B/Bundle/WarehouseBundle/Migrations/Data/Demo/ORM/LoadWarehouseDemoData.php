<?php

namespace OroB2B\Bundle\WarehouseBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\UserBundle\Entity\User;

use OroB2B\Bundle\WarehouseBundle\Entity\Warehouse;

class LoadWarehouseDemoData implements FixtureInterface
{
    /**
     * @inheritDoc
     */
    public function load(ObjectManager $manager)
    {
        /** @var EntityManager $manager */
        $user = $this->getUser($manager);
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

    /**
     * @param EntityManager $manager
     * @return User
     * @throws \LogicException
     */
    protected function getUser(EntityManager $manager)
    {
        $user = $manager->getRepository('OroUserBundle:User')
            ->createQueryBuilder('user')
            ->orderBy('user.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleResult();

        if (!$user) {
            throw new \LogicException('There are no users in system');
        }

        return $user;
    }
}
