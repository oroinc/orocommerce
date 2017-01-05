<?php

namespace Oro\Bundle\PaymentBundle\Method\View;

class PaymentMethodViewProvidersRegistry
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
     * @param array $methodTypes
     * @return PaymentMethodViewInterface[]
     */
    public function getPaymentMethodViews(array $methodTypes)
    {
        $result = [];
        foreach ($this->providers as $provider) {
            $result = array_merge($result, $provider->getPaymentMethodViews($methodTypes));
        }
        return $result;
    }

    /**
     * @param string $paymentMethod
     * @return PaymentMethodViewInterface
     */
    public function getPaymentMethodView($paymentMethod)
    {
        foreach ($this->providers as $provider) {
            if ($provider->hasPaymentMethodView($paymentMethod)) {
                return $provider->getPaymentMethodView($paymentMethod);
            }
        }
        throw new \InvalidArgumentException('There is no payment method view for "'.$paymentMethod.'"');
    }
}
