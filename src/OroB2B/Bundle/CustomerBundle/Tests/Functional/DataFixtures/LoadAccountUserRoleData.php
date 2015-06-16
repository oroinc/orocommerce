<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUserRole;

class LoadAccountUserRoleData extends AbstractFixture
{
    const LABEL = 'Partner';

    /**
     * @var array
     */
    protected $userRoles = [
        [
            'label' => self::LABEL
        ]
    ];

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->userRoles as $userRole) {
            $entity = new AccountUserRole();
            $entity->setLabel($userRole['label']);

            $manager->persist($entity);
        }

        $manager->flush();
    }
}
