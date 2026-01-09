<?php

namespace Oro\Bundle\PricingBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Migrate existing Combined Price Lists to optimized naming strategy when Minimal Pricing strategy used
 */
class MigrateMinimalStrategyNaming extends AbstractFixture implements
    ContainerAwareInterface
{
    use ContainerAwareTrait;

    #[\Override]
    public function load(ObjectManager $manager)
    {
        $this->container->get('oro_pricing.migrations.minimal_strategy_naming_migration')->migrate();
    }
}
