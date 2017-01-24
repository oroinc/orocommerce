<?php

namespace Oro\Bundle\MoneyOrderBundle\Method;

use Oro\Bundle\MoneyOrderBundle\Method\Config\Provider\MoneyOrderConfigProvider;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;

class MoneyOrderMethodProvider implements PaymentMethodProviderInterface
{
    /**
     * @var MoneyOrderConfigProvider
     */
    private $configProvider;

    /**
     * @param MoneyOrderConfigProvider $configProvider
     */
    public function __construct(MoneyOrderConfigProvider $configProvider)
    {
        $this->configProvider = $configProvider;
    }

    /**
     * @return MoneyOrder[]
     */
    public function getPaymentMethods()
    {
        $methods = [];
        foreach ($this->configProvider->getPaymentConfigs() as $config) {
            $method = new MoneyOrder($config);
            $methods[$method->getIdentifier()] = $method;
        }

        return $methods;
    }

    /**
     * @param string $identifier
     *
     * @return MoneyOrder|null
     */
    public function getPaymentMethod($identifier)
    {
        if (!$this->hasPaymentMethod($identifier)) {
            return null;
        }

        $methods = $this->getPaymentMethods();

        return $methods[$identifier];
    }

    /**
     * @param string $identifier
     *
     * @return bool
     */
    public function hasPaymentMethod($identifier)
    {
        $methods = $this->getPaymentMethods();

        return array_key_exists($identifier, $methods);
    }

    /**
     * @return string
     */
    public function getType()
    {
        return MoneyOrder::TYPE;
    }
}
