<?php

namespace Oro\Bundle\PaymentBundle\Method\Provider;

use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;

/**
 * The registry of payment method providers.
 */
class CompositePaymentMethodProvider implements PaymentMethodProviderInterface
{
    /**
     * @param iterable<PaymentMethodProviderInterface> $innerProviders
     */
    public function __construct(private iterable $innerProviders)
    {
    }

    #[\Override]
    public function getPaymentMethods(): array
    {
        $paymentMethods = [];
        foreach ($this->innerProviders as $paymentMethodProvider) {
            $paymentMethods[] = $paymentMethodProvider->getPaymentMethods();
        }

        return array_merge(...$paymentMethods);
    }

    #[\Override]
    public function getPaymentMethod($identifier): PaymentMethodInterface
    {
        foreach ($this->innerProviders as $paymentMethodProvider) {
            if ($paymentMethodProvider->hasPaymentMethod($identifier)) {
                return $paymentMethodProvider->getPaymentMethod($identifier);
            }
        }

        throw new \InvalidArgumentException('There is no payment method for "' . $identifier . '" identifier');
    }

    #[\Override]
    public function hasPaymentMethod($identifier): bool
    {
        foreach ($this->innerProviders as $paymentMethodProvider) {
            if ($paymentMethodProvider->hasPaymentMethod($identifier)) {
                return true;
            }
        }

        return false;
    }
}
