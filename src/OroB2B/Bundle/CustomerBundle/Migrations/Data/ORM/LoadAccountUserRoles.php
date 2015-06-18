<?php

namespace OroB2B\Bundle\CustomerBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\CustomerBundle\Entity\AccountUserRole;

class LoadAccountUserRoles extends AbstractFixture
{
    /**
     * @var array
     */
    protected $defaultRoles = [
        AccountUser::ROLE_ADMINISTRATOR => 'Administrator',
        AccountUser::ROLE_BUYER => 'Buyer',
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->defaultRoles as $name => $label) {
            $role = new AccountUserRole($name);
            $role->setLabel($label);
            $manager->persist($role);
        }

        $manager->flush();
    }
}
