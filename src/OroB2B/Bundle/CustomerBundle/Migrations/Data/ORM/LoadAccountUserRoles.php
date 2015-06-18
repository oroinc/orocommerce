<?php

namespace OroB2B\Bundle\CustomerBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUserRole;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class LoadAccountUserRoles extends AbstractFixture implements DependentFixtureInterface
{
    const ADMINISTRATOR = 'Administrator';
    const BUYER = 'Buyer';

    /**
     * @var array
     */
    protected $defaultRoles = [
        self::ADMINISTRATOR,
        self::BUYER
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return ['OroB2B\Bundle\WebsiteBundle\Migrations\Data\ORM\LoadWebsiteData'];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->defaultRoles as $roleLabel) {
            $role = new AccountUserRole();
            $role->setLabel($roleLabel)
                ->setRole($roleLabel);

            // By default Buyer role is default role for websites
            if ($roleLabel === self::BUYER) {
                $this->setWebsiteDefaultRoles($manager, $role);
            }

            $manager->persist($role);
        }

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param AccountUserRole $role
     */
    protected function setWebsiteDefaultRoles(ObjectManager $manager, AccountUserRole $role)
    {
        $websites = $manager->getRepository('OroB2BWebsiteBundle:Website')->findAll();

        foreach ($websites as $website) {
            $role->addWebsite($website);
        }
    }
}
