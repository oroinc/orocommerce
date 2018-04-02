<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\EventListener\ORM;

use Oro\Bundle\OrderBundle\DependencyInjection\Configuration;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @method ContainerInterface getContainer()
 */
trait PreviouslyPurchasedFeatureTrait
{
    public function enablePreviouslyPurchasedFeature(Website $website)
    {
        $this->getContainer()->get('oro_config.manager')
            ->set(Configuration::getConfigKey(Configuration::CONFIG_KEY_ENABLE_PURCHASE_HISTORY), true, $website);
        $this->getContainer()->get('oro_config.manager')->flush();
        $this->getContainer()->get('oro_featuretoggle.checker.feature_checker')->resetCache();
    }


    public function disablePreviouslyPurchasedFeature(Website $website)
    {
        $this->getContainer()->get('oro_config.manager')
            ->set(Configuration::getConfigKey(Configuration::CONFIG_KEY_ENABLE_PURCHASE_HISTORY), false, $website);
        $this->getContainer()->get('oro_config.manager')->flush();
        $this->getContainer()->get('oro_featuretoggle.checker.feature_checker')->resetCache();
    }
}
