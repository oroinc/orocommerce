<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;

class LoadGroups extends AbstractFixture
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->createGroup($manager, 'customer_group.group1');
        $this->createGroup($manager, 'customer_group.group2');
        $this->createGroup($manager, 'customer_group.group3');

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string $name
     * @return AccountGroup
     */
    protected function createGroup(ObjectManager $manager, $name)
    {
        $group = new AccountGroup();
        $group->setName($name);
        $manager->persist($group);
        $this->addReference($name, $group);

        return $group;
    }
}
