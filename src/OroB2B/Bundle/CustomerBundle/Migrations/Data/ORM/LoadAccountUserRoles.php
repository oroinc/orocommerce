<?php

namespace OroB2B\Bundle\CustomerBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUserRole;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class LoadAccountUserRoles extends AbstractFixture implements DependentFixtureInterface
{
    const ADMINISTRATOR = 'ADMINISTRATOR';
    const BUYER = 'BUYER';

    /**
     * @var array
     */
    protected $defaultRoles = [
        self::ADMINISTRATOR => 'Administrator',
        self::BUYER => 'Buyer',
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
        foreach ($this->defaultRoles as $name => $label) {
            $role = new AccountUserRole(AccountUserRole::PREFIX_ROLE . $name);
            $role->setLabel($label);
            $manager->persist($role);

            // By default Buyer role is default role for websites
            if ($name === self::BUYER) {
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
