<?php

namespace OroB2B\Bundle\WebsiteBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\LoadOrganizationAndBusinessUnitData;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadWebsiteData extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    const DEFAULT_WEBSITE_NAME = 'Default';

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return [
            LoadOrganizationAndBusinessUnitData::class,
        ];
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        /** @var OrganizationInterface $organization */
        $organization = $this->getReference('default_organization');

        $businessUnit = $manager
            ->getRepository('OroOrganizationBundle:BusinessUnit')
            ->findOneBy(['name' => LoadOrganizationAndBusinessUnitData::MAIN_BUSINESS_UNIT]);

        $website = new Website();
        $website
            ->setName(self::DEFAULT_WEBSITE_NAME)
            ->setOrganization($organization)
            ->setOwner($businessUnit)
            ->setDefault(true);

        $manager->persist($website);
        /** @var EntityManager $manager */
        $manager->flush($website);

        $configManager = $this->container->get('oro_config.manager');

        $url = $this->container->get('oro_config.manager')->get('oro_ui.application_url');
        // Store website url in configuration
        $configManager->set('oro_b2b_website.url', $url, $website->getId());
        $configManager->set('oro_b2b_website.secure_url', $url, $website->getId());
        $configManager->flush();
    }
}
