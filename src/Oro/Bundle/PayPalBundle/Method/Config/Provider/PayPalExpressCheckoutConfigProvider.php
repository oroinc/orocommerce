<?php

namespace Oro\Bundle\PayPalBundle\Method\Config\Provider;

use Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfigInterface;

class PayPalExpressCheckoutConfigProvider extends AbstractPayPalConfigProvider implements
    PayPalExpressCheckoutConfigProviderInterface
{
    /**
     * @var PayPalExpressCheckoutConfigInterface[]
     */
    protected $configs = [];

    /**
     * @return PayPalExpressCheckoutConfigInterface[]
     */
    public function getPaymentConfigs()
    {
        if (0 === count($this->configs)) {
            return $this->configs = $this->collectConfigs();
        }

        return $this->configs;
    }

    /**
     * @param string $identifier
     * @return PayPalExpressCheckoutConfigInterface|null
     */
    public function getPaymentConfig($identifier)
    {
        if (!$this->hasPaymentConfig($identifier)) {
            return null;
        }

        $configs = $this->getPaymentConfigs();

        return $configs[$identifier];
    }
}
