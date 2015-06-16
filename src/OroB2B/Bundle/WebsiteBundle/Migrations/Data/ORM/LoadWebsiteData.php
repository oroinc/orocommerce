<?php

namespace OroB2B\Bundle\WebsiteBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\LoadOrganizationAndBusinessUnitData;

use OroB2B\Bundle\WebsiteBundle\Entity\Locale;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class LoadWebsiteData extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
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
            'Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\LoadOrganizationAndBusinessUnitData',
            'OroB2B\Bundle\WebsiteBundle\Migrations\Data\ORM\LoadLocaleData'
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

        $url = parse_url($this->container->get('oro_config.manager')->get('oro_ui.application_url'));

        /** @var Locale $locale */
        $locale = $this->getReference('default_website_locale');

        $website = new Website();
        $website
            ->setName('Default')
            ->setOrganization($organization)
            ->setOwner($businessUnit)
            ->setUrl($url['host'])
            ->addLocale($locale);

        $manager->persist($website);
        /** @var EntityManager $manager */
        $manager->flush($website);
    }
}
