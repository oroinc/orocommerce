<?php

namespace Oro\Bundle\PaymentBundle\Method\Config;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

abstract class AbstractPaymentConfig
{
    /** @var ConfigManager */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @return string
     */
    abstract protected function getPaymentExtensionAlias();

    /**
     * @param string $key
     * @return mixed
     */
    protected function getConfigValue($key)
    {
        $key = $this->getPaymentExtensionAlias() . ConfigManager::SECTION_MODEL_SEPARATOR . $key;

        return $this->configManager->get($key);
    }
}
