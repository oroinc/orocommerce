<?php

namespace Oro\Bundle\PayPalBundle\Method\Config\Provider;

use Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfigInterface;

class PayPalCreditCardConfigProvider extends AbstractPayPalConfigProvider implements
    PayPalCreditCardConfigProviderInterface
{
    /**
     * @var PayPalCreditCardConfigInterface[]
     */
    protected $configs;

    /**
     * @return PayPalCreditCardConfigInterface[]
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
}
