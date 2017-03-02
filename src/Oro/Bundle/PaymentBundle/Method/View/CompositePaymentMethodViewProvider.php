<?php

namespace Oro\Bundle\PaymentBundle\Method\View;

class CompositePaymentMethodViewProvider implements PaymentMethodViewProviderInterface
{
    /**
     * @var PaymentMethodViewProviderInterface[]
     */
    private $providers = [];

    /**
     * Add payment method type to the registry
     *
     * @param PaymentMethodViewProviderInterface $provider
     */
    public function addProvider(PaymentMethodViewProviderInterface $provider)
    {
        $this->providers[] = $provider;
    }

    /**
     * {@inheritDoc}
     */
    public function getPaymentMethodViews(array $identifiers)
    {
        $result = [];
        foreach ($this->providers as $provider) {
            $result = array_merge($result, $provider->getPaymentMethodViews($identifiers));
        }
        
        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function getPaymentMethodView($identifier)
    {
        foreach ($this->providers as $provider) {
            if ($provider->hasPaymentMethodView($identifier)) {
                return $provider->getPaymentMethodView($identifier);
            }
        }
        
        throw new \InvalidArgumentException('There is no payment method view for "'.$identifier.'"');
    }

    /**
     * {@inheritDoc}
     */
    public function hasPaymentMethodView($identifier)
    {
        foreach ($this->providers as $provider) {
            if ($provider->hasPaymentMethodView($identifier)) {
                return true;
            }
        }
        
        return false;
    }
}
