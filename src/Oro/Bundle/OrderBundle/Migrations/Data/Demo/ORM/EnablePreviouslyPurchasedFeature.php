<?php

namespace Oro\Bundle\OrderBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrderBundle\DependencyInjection\Configuration as OrderConfig;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Enables "Previously Purchased" feature on demo instance.
 */
class EnablePreviouslyPurchasedFeature extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    use ContainerAwareTrait;

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadPaymentTermToOrderDemoData::class
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $configManager = $this->container->get('oro_config.global');
        $configManager->set(OrderConfig::getConfigKey(OrderConfig::CONFIG_KEY_ENABLE_PURCHASE_HISTORY), true);
        $configManager->flush();
    }
}
