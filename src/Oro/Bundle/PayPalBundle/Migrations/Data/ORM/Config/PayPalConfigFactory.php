<?php

namespace Oro\Bundle\PaymentBundle\Migrations\Data\ORM\Config;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class PayPalConfigFactory
{
    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var ManagerRegistry
     */
    private $doctrine;

    /**
     * @param ManagerRegistry $doctrine
     * @param ConfigManager   $configManager
     */
    public function __construct(ManagerRegistry $doctrine, ConfigManager $configManager)
    {
        $this->doctrine = $doctrine;
        $this->configManager = $configManager;
    }

    /**
     * @return PayPalConfig
     */
    public function createPaymentsProConfig()
    {
        return $this->createPayPalConfig(PayPalConfigKeysProviderFactory::createPaymentsProConfigKeyProvider());
    }

    /**
     * @return PayPalConfig
     */
    public function createPayflowGatewayConfig()
    {
        return $this->createPayPalConfig(PayPalConfigKeysProviderFactory::createPayflowGatewayConfigKeyProvider());
    }

    /**
     * @param PayPalConfigKeysProvider $keysProvider
     *
     * @return PayPalConfig
     */
    protected function createPayPalConfig(PayPalConfigKeysProvider $keysProvider)
    {
        return new PayPalConfig($this->doctrine, $this->configManager, $keysProvider);
    }
}
