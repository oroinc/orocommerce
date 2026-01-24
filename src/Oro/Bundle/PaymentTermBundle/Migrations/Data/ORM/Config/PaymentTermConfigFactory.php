<?php

namespace Oro\Bundle\PaymentTermBundle\Migrations\Data\ORM\Config;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

/**
 * Factory for creating payment term configuration objects from system configuration.
 *
 * This factory creates {@see PaymentTermConfig} instances that read payment term settings from the system configuration
 * manager, used during data migrations to access legacy payment term configuration values.
 */
class PaymentTermConfigFactory
{
    /**
     * @var ConfigManager
     */
    private $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @return PaymentTermConfig
     */
    public function createPaymentTermConfig()
    {
        return new PaymentTermConfig($this->configManager);
    }
}
