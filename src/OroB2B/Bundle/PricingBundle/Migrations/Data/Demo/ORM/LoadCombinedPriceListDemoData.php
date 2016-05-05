<?php

namespace OroB2B\Bundle\PricingBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadCombinedPriceListDemoData extends AbstractFixture implements
    ContainerAwareInterface,
    DependentFixtureInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\PricingBundle\Migrations\Data\Demo\ORM\LoadPriceListToAccountDemoData',
            'OroB2B\Bundle\PricingBundle\Migrations\Data\Demo\ORM\LoadPriceListToAccountGroupDemoData',
            'OroB2B\Bundle\PricingBundle\Migrations\Data\Demo\ORM\LoadPriceListToWebsiteDemoData',
            'OroB2B\Bundle\PricingBundle\Migrations\Data\Demo\ORM\LoadProductPriceDemoData',
        ];
    }

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $this->container->get('orob2b_pricing.recalculate_triggers_filler.scope_recalculate_triggers_filler')
            ->fillTriggersForRecalculate([], [], []);
        $this->container->get('orob2b_pricing.builder.queue_consumer')->process();
    }

    /**
     * Sets the Container.
     *
     * @param ContainerInterface|null $container A ContainerInterface instance or null
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
}
