<?php

namespace Oro\Bundle\PaymentBundle\Method\Provider;

class CompositePaymentMethodProvider implements PaymentMethodProviderInterface
{
    /**
     * @var PaymentMethodProviderInterface[]
     */
    protected $providers = [];

    /**
     * @param PaymentMethodProviderInterface $provider
     */
    public function addProvider(PaymentMethodProviderInterface $provider)
    {
        $this->providers[] = $provider;
    }

    /**
     * {@inheritDoc}
     */
    public function getPaymentMethods()
    {
        $result = [];
        foreach ($this->providers as $provider) {
            $result = array_merge($result, $provider->getPaymentMethods());
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function getPaymentMethod($identifier)
    {
        foreach ($this->providers as $provider) {
            if ($provider->hasPaymentMethod($identifier)) {
                return $provider->getPaymentMethod($identifier);
            }
        }
        throw new \InvalidArgumentException('There is no payment method for "' . $identifier . '" identifier');
    }

    /**
     * {@inheritDoc}
     */
    public function hasPaymentMethod($identifier)
    {
        foreach ($this->providers as $provider) {
            if ($provider->hasPaymentMethod($identifier)) {
                return true;
            }
        }

        return false;
    }
}
