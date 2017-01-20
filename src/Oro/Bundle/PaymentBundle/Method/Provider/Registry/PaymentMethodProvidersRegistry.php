<?php

namespace Oro\Bundle\PaymentBundle\Method\Provider\Registry;

use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;

class PaymentMethodProvidersRegistry implements PaymentMethodProvidersRegistryInterface
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
     * {@inheritdoc}
     */
    public function getPaymentMethodProviders()
    {
        return $this->providers;
    }
}
