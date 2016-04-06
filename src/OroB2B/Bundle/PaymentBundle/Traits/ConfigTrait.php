<?php

namespace OroB2B\Bundle\PaymentBundle\Traits;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\PaymentBundle\DependencyInjection\OroB2BPaymentExtension;

trait ConfigTrait
{
    /** @var ConfigManager */
    protected $configManager;

    /**
     * @param string $key
     * @return mixed
     */
    protected function getConfigValue($key)
    {
        if (null === $this->configManager) {
            throw new \RuntimeException('ConfigManager is not injected');
        }

        $key = OroB2BPaymentExtension::ALIAS . ConfigManager::SECTION_MODEL_SEPARATOR . $key;

        return $this->configManager->get($key);
    }
}
