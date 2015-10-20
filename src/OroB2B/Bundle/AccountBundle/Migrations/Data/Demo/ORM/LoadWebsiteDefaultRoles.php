<?php

namespace OroB2B\Bundle\AccountBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\WebsiteBundle\Migrations\Data\ORM\LoadWebsiteData;

class LoadWebsiteDefaultRoles extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\WebsiteBundle\Migrations\Data\ORM\LoadWebsiteData',
            'OroB2B\Bundle\WebsiteBundle\Migrations\Data\Demo\ORM\LoadWebsiteDemoData'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $websites = $manager->getRepository('OroB2BWebsiteBundle:Website')->findAll();
        $defaultWebsite = $manager->getRepository('OroB2BWebsiteBundle:Website')
            ->findOneBy(['name' => LoadWebsiteData::DEFAULT_WEBSITE_NAME]);

        // Remove default site. Default role for it already exist
        unset($websites[array_search($defaultWebsite, $websites)]);

        $allRoles = $manager->getRepository('OroB2BAccountBundle:AccountUserRole')->findAll();
        foreach ($websites as $website) {
            $role = $allRoles[mt_rand(0, count($allRoles) - 1)];
            $role->addWebsite($website);

            $manager->persist($role);
        }

        $manager->flush();
    }
}
