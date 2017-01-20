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
        $this->providers[] = $provider;
    }

    /**
     * @return PaymentMethodProviderInterface[]
     */
    public function getPaymentMethodProviders()
    {
        return $this->providers;
    }
}
