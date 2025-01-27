<?php

namespace Oro\Bundle\CheckoutBundle\Api;

/**
 * Provides the checkout payment sub-resource name.
 */
class CheckoutPaymentSubresourceNameProvider
{
    /**
     * @param iterable<CheckoutPaymentSubresourceProviderInterface> $providers
     */
    public function __construct(
        private readonly iterable $providers
    ) {
    }

    public function getCheckoutPaymentSubresourceName(string $paymentMethod): ?string
    {
        foreach ($this->providers as $provider) {
            if ($provider->isSupportedPaymentMethod($paymentMethod)) {
                return $provider->getCheckoutPaymentSubresourceName();
            }
        }

        return null;
    }
}
