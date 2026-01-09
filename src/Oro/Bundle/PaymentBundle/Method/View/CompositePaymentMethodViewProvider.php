<?php

namespace Oro\Bundle\PaymentBundle\Method\View;

/**
 * The registry of payment method view providers.
 */
class CompositePaymentMethodViewProvider implements PaymentMethodViewProviderInterface
{
    /**
     * @param iterable<PaymentMethodViewProviderInterface> $innerProviders
     */
    public function __construct(private iterable $innerProviders)
    {
    }

    #[\Override]
    public function getPaymentMethodViews(array $identifiers): array
    {
        $paymentMethodViews = [];
        foreach ($identifiers as $identifier) {
            foreach ($this->innerProviders as $paymentMethodViewProvider) {
                if ($paymentMethodViewProvider->hasPaymentMethodView($identifier)) {
                    $paymentMethodViews[] = $paymentMethodViewProvider->getPaymentMethodView($identifier);
                }
            }
        }

        return $paymentMethodViews;
    }

    #[\Override]
    public function getPaymentMethodView($identifier): PaymentMethodViewInterface
    {
        foreach ($this->innerProviders as $paymentMethodViewProvider) {
            if ($paymentMethodViewProvider->hasPaymentMethodView($identifier)) {
                return $paymentMethodViewProvider->getPaymentMethodView($identifier);
            }
        }

        throw new \InvalidArgumentException('There is no payment method view for "' . $identifier . '"');
    }

    #[\Override]
    public function hasPaymentMethodView($identifier): bool
    {
        foreach ($this->innerProviders as $paymentMethodViewProvider) {
            if ($paymentMethodViewProvider->hasPaymentMethodView($identifier)) {
                return true;
            }
        }

        return false;
    }
}
