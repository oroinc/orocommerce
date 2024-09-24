<?php

namespace Oro\Bundle\OrderBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\OrderBundle\DependencyInjection\Configuration as OrderConfiguration;
use Oro\Bundle\OrderBundle\Entity\Order;

/**
 * A service to get order related configuration.
 */
class OrderConfigurationProvider implements OrderConfigurationProviderInterface
{
    /** @var ConfigManager */
    protected $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * Returns 'System'-level configuration value
     *
     */
    #[\Override]
    public function getNewOrderInternalStatus(Order $order)
    {
        return $this->getConfigValue(OrderConfiguration::CONFIG_KEY_NEW_ORDER_INTERNAL_STATUS, null);
    }

    #[\Override]
    public function isAutomaticCancellationEnabled($identifier = null)
    {
        return $this->getConfigValue(OrderConfiguration::CONFIG_KEY_ENABLE_CANCELLATION, $identifier);
    }

    #[\Override]
    public function getTargetInternalStatus($identifier = null)
    {
        return $this->getConfigValue(OrderConfiguration::CONFIG_KEY_TARGET_INTERNAL_STATUS, $identifier);
    }

    #[\Override]
    public function getApplicableInternalStatuses($identifier = null)
    {
        return $this->getConfigValue(OrderConfiguration::CONFIG_KEY_APPLICABLE_INTERNAL_STATUSES, $identifier);
    }

    #[\Override]
    public function isExternalStatusManagementEnabled($identifier = null)
    {
        return $this->getConfigValue(OrderConfiguration::CONFIG_KEY_ENABLE_EXTERNAL_STATUS_MANAGEMENT, $identifier);
    }

    /**
     * @param string $key
     * @param null|int|object $identifier
     *
     * @return array|string|int
     */
    protected function getConfigValue($key, $identifier = null)
    {
        $config = $this->getConfig($key, $identifier);

        return $config['value'] ?? null;
    }

    /**
     * @param string $key
     * @param null|int|object $identifier
     *
     * @return array
     */
    protected function getConfig($key, $identifier = null)
    {
        return $this->configManager->get(OrderConfiguration::getConfigKey($key), false, true, $identifier);
    }
}
