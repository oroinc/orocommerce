<?php

namespace Oro\Bundle\SaleBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\SaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\SaleBundle\Provider\ContactInfoSourceOptionsProvider;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadSalesContactInfoData extends AbstractFixture implements
    FixtureInterface,
    ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

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
    public function load(ObjectManager $manager)
    {
        $configManager = $this->container->get('oro_config.global');

        $configKey = Configuration::getConfigKeyByName(Configuration::CONTACT_INFO_SOURCE_DISPLAY);
        $configManager->set($configKey, ContactInfoSourceOptionsProvider::PRE_CONFIGURED);

        $configKey = Configuration::getConfigKeyByName(Configuration::CONTACT_DETAILS);
        $text = "John Doe\n(800) 555-0100\n(800) 555-0199\njohn.doe@example.com";
        $configManager->set($configKey, $text);
        $configManager->flush();
    }
}
