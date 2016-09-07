<?php

namespace Oro\Bundle\PricingBundle\Migrations\Data\ORM;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\PricingBundle\SystemConfig\PriceListConfig;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;

class SetDefaultPriceList extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
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
            'Oro\Bundle\PricingBundle\Migrations\Data\ORM\LoadPriceListData',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $class = 'OroPricingBundle:PriceList';
        /** @var PriceListRepository $repository */
        $repository = $this->container->get('doctrine')->getManagerForClass($class)
            ->getRepository($class);
        $defaultPriceList = $repository->getDefault();

        $configManager = $this->container->get('oro_config.global');
        $configManager->set(
            'oro_b2b_pricing.default_price_lists',
            [new PriceListConfig($defaultPriceList, 100, true)]
        );
        $configManager->flush();
        $this->container->get('oro_pricing.builder.combined_price_list_builder')->build();
    }
}
