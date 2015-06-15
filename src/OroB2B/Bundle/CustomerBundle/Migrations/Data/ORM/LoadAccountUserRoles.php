<?php

namespace OroB2B\Bundle\CustomerBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUserRole;

class LoadAccountUserRoles extends AbstractFixture
{
    /**
     * @var array
     */
    protected $defaultRoles = [
        'Administrator'
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->defaultRoles as $roleLabel) {
            $role = new AccountUserRole();
            $role->setLabel($roleLabel)
                ->setRole($roleLabel);
            $manager->persist($role);
        }

        $manager->flush();
    }
}
