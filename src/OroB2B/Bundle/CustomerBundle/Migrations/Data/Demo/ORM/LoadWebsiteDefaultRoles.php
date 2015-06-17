<?php

namespace OroB2B\Bundle\CustomerBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUserRole;
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
        $role = $this->findRole($manager);
        $this->setWebsiteDefaultRoles($manager, $role);

        $manager->persist($role);
        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @return mixed
     */
    protected function findRole(ObjectManager $manager)
    {
        return $manager->getRepository('OroB2BCustomerBundle:AccountUserRole')
            ->createQueryBuilder('r')
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleResult();
    }

    /**
     * @param ObjectManager $manager
     * @param AccountUserRole $role
     */
    protected function setWebsiteDefaultRoles(ObjectManager $manager, AccountUserRole $role)
    {
        $websites = $manager->getRepository('OroB2BWebsiteBundle:Website')->findAll();
        $defaultWebsite = $manager->getRepository('OroB2BWebsiteBundle:Website')
            ->findOneBy(['name' => LoadWebsiteData::DEFAULT_WEBSITE_NAME]);

        // Remove default site. Default role for it already exist
        unset($websites[array_search($defaultWebsite, $websites)]);

        foreach ($websites as $website) {
            $role->addWebsite($website);
        }
    }
}
