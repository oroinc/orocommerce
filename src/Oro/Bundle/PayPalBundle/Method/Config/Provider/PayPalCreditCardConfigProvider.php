<?php

namespace Oro\Bundle\PayPalBundle\Method\Config\Provider;

use Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfig;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfigInterface;

class PayPalCreditCardConfigProvider extends PayPalConfigProvider implements PayPalCreditCardConfigProviderInterface
{
    /**
     * @var array|PayPalCreditCardConfigInterface[]
     */
    protected $configs = [];

    /**
     * @return array|PayPalCreditCardConfigInterface[]
     */
    public function getPaymentConfigs()
    {
        if (empty($this->configs)) {
            $this->fillConfigs();
        }

        return $this->configs;
    }

    /**
     * @param string $identifier
     * @return PayPalCreditCardConfigInterface|null
     */
    public function getPaymentConfig($identifier)
    {
        if (!$this->hasPaymentConfig($identifier)) {
            return null;
        }

        $configs = $this->getPaymentConfigs();

        return $configs[$identifier];
    }

    protected function fillConfigs()
    {
        $channels = $this->getEnabledIntegrationChannels();

        foreach ($channels as $channel) {
            $config = new PayPalCreditCardConfig($channel, $this->encoder);
            $this->configs[$config->getPaymentMethodIdentifier()] = $config;
        }
    }
}
