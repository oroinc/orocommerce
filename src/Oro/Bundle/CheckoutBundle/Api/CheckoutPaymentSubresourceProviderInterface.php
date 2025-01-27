<?php

namespace Oro\Bundle\CheckoutBundle\Api;

/**
 * Represents a service that provides information for the checkout payment sub-resource.
 */
interface CheckoutPaymentSubresourceProviderInterface
{
    public function isSupportedPaymentMethod(string $paymentMethod): bool;

    public function getCheckoutPaymentSubresourceName(): ?string;
}
