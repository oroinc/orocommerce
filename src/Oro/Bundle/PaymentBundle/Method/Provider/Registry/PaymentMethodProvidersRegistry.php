<?php

namespace Oro\Bundle\PaymentBundle\Method\Provider\Registry;

use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;

/**
 * @deprecated since 1.1
 *
 * @see \Oro\Bundle\PaymentBundle\Method\Provider\CompositePaymentMethodProvider
 */
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
     * {@inheritDoc}
     */
    public function getPaymentMethodProviders()
    {
        return $this->providers;
    }
}
