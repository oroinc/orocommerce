<?php

namespace Oro\Bundle\PricingBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\PricingBundle\PricingStrategy\MinimalPricesCombiningStrategy;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

use Oro\Bundle\PricingBundle\DependencyInjection\Configuration;

class UpdatePriceListStrategyConfig extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $name = sprintf('%s.%s', Configuration::ROOT_NODE, Configuration::PRICE_LIST_STRATEGIES);

        $configManager = $this->container->get('oro_config.global');
        $configManager->set($name, MinimalPricesCombiningStrategy::NAME);

        $configManager->flush();
        $this->container->get('oro_pricing.builder.combined_price_list_builder')->build();
    }
}
