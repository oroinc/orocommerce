<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\CustomerBundle\Entity\CustomerGroup;

class LoadGroups extends AbstractFixture
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->createGroup($manager, 'customer_group.group1');
        $this->createGroup($manager, 'customer_group.group2');

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string $name
     * @return CustomerGroup
     */
    protected function createGroup(ObjectManager $manager, $name)
    {
        $group = new CustomerGroup();
        $group->setName($name);
        $manager->persist($group);
        $this->addReference($name, $group);

        return $group;
    }
}
