<?php

namespace Oro\Bundle\PaymentTermBundle\Migrations\Data\ORM\Config;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

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
