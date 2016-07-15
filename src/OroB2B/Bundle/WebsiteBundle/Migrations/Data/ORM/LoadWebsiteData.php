<?php

namespace OroB2B\Bundle\WebsiteBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\LocaleBundle\Migrations\Data\ORM\LoadLocalizationData;
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
            'Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\LoadOrganizationAndBusinessUnitData'
        ];
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $organization = $this->getReference('default_organization');

        $businessUnit = $manager
            ->getRepository('OroOrganizationBundle:BusinessUnit')
            ->findOneBy(['name' => LoadOrganizationAndBusinessUnitData::MAIN_BUSINESS_UNIT]);

        $url = $this->container->get('oro_config.manager')->get('oro_ui.application_url');

        $website = new Website();
        $website
            ->setName(self::DEFAULT_WEBSITE_NAME)
            ->setOrganization($organization)
            ->setOwner($businessUnit)
            ->setUrl($url);

        $manager->persist($website);
        /** @var EntityManager $manager */
        $manager->flush($website);
    }
}
