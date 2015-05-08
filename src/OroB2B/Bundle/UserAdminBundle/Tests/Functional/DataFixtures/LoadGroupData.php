<?php

namespace OroB2B\Bundle\UserAdminBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\UserAdminBundle\Entity\Group;

class LoadGroupData extends AbstractFixture
{
    const GROUP_NAME = 'Test Group Name';

    /**
     * @var array
     */
    protected $groups = [
        [
            'name' => self::GROUP_NAME,
        ]
    ];

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->groups as $group) {
            $entity = new Group($group['name']);

            $manager->persist($entity);
        }

        $manager->flush();
    }
}
