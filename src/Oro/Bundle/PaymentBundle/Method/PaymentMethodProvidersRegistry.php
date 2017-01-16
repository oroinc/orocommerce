<?php

namespace Oro\Bundle\PaymentBundle\Method;

class PaymentMethodProvidersRegistry
{
    /**
     * @var PaymentMethodProviderInterface[]
     */
    private $providers = [];

    /**
     * @param PaymentMethodProviderInterface $provider
     */
    public function addProvider(PaymentMethodProviderInterface $provider)
    {
        $this->providers[$provider->getType()] = $provider;
    }

    /**
     * @param string $type
     * @return PaymentMethodProviderInterface
     * @throws \InvalidArgumentException
     */
    public function getPaymentMethodProvider($type)
    {
        if ($this->hasPaymentMethodProvider($type)) {
            return $this->providers[$type];
        }

        return null;
    }

    /**
     * @return PaymentMethodProviderInterface[]
     */
    public function getPaymentMethodProviders()
    {
        return $this->providers;
    }

    /**
     * @param string $type
     * @return bool
     */
    public function hasPaymentMethodProvider($type)
    {
        return array_key_exists($type, $this->providers);
    }
}
