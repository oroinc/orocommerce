<?php

namespace Oro\Bundle\CheckoutBundle\Api;

use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;

/**
 * Provides the checkout payment sub-resource information for payment method.
 */
class CheckoutPaymentSubresourceProvider implements CheckoutPaymentSubresourceProviderInterface
{
    public function __construct(
        private readonly PaymentMethodProviderInterface $paymentMethodProvider,
        private readonly string $subresourceName
    ) {
    }

    #[\Override]
    public function isSupportedPaymentMethod(string $paymentMethod): bool
    {
        return $this->paymentMethodProvider->hasPaymentMethod($paymentMethod);
    }

    #[\Override]
    public function getCheckoutPaymentSubresourceName(): ?string
    {
        return $this->subresourceName;
    }
}
