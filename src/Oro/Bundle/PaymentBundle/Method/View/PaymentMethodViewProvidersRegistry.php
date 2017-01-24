<?php

namespace Oro\Bundle\PaymentBundle\Method\View;

class PaymentMethodViewProvidersRegistry implements PaymentMethodViewProvidersRegistryInterface
{
    /** @var PaymentMethodViewProviderInterface[] */
    protected $providers = [];

    /**
     * Add payment method type to the registry
     * @param PaymentMethodViewProviderInterface $provider
     */
    public function addProvider(PaymentMethodViewProviderInterface $provider)
    {
        $this->providers[] = $provider;
    }

    /**
     * @param array $methodIdentifiers
     * @return PaymentMethodViewInterface[]
     */
    public function getPaymentMethodViews(array $methodIdentifiers)
    {
        $result = [];
        foreach ($this->providers as $provider) {
            $result = array_merge($result, $provider->getPaymentMethodViews($methodIdentifiers));
        }
        return $result;
    }

    /**
     * @param string $methodIdentifier
     * @return PaymentMethodViewInterface
     */
    public function getPaymentMethodView($methodIdentifier)
    {
        foreach ($this->providers as $provider) {
            if ($provider->hasPaymentMethodView($methodIdentifier)) {
                return $provider->getPaymentMethodView($methodIdentifier);
            }
        }
        throw new \InvalidArgumentException('There is no payment method view for "'.$methodIdentifier.'"');
    }
}
