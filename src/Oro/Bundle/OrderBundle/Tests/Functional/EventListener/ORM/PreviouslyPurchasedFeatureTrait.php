<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\EventListener\ORM;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\OrderBundle\DependencyInjection\Configuration;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @method static ContainerInterface getContainer()
 */
trait PreviouslyPurchasedFeatureTrait
{
    use ConfigManagerAwareTestTrait;

    /**
     * @param string|null $scope
     * @param object|int|null $scopeIdentifier
     */
    public function enablePreviouslyPurchasedFeature(?string $scope = 'global', $scopeIdentifier = null): void
    {
        $key = Configuration::getConfigKey(Configuration::CONFIG_KEY_ENABLE_PURCHASE_HISTORY);

        $configManager = self::getConfigManager($scope);
        $configManager->set($key, true, $scopeIdentifier);
        $configManager->flush();
        // Clears cache in general config manager.
        self::getConfigManager(null)->reload();

        self::getContainer()->get('oro_featuretoggle.checker.feature_checker')->resetCache();
    }

    /**
     * @param string|null $scope
     * @param object|int|null $scopeIdentifier
     */
    public function disablePreviouslyPurchasedFeature(?string $scope = 'global', $scopeIdentifier = null): void
    {
        $key = Configuration::getConfigKey(Configuration::CONFIG_KEY_ENABLE_PURCHASE_HISTORY);

        $configManager = self::getConfigManager($scope);
        $configManager->set($key, false, $scopeIdentifier);
        $configManager->flush();
        // Clears cache in general config manager.
        self::getConfigManager(null)->reload();

        self::getContainer()->get('oro_featuretoggle.checker.feature_checker')->resetCache();
    }
}
