<?php

namespace Oro\Bundle\PaymentBundle\Method\View;

use Oro\Bundle\PaymentBundle\Method\PaymentMethodGroupAwareInterface;

/**
 * The registry of payment method view providers.
 */
class CompositePaymentMethodViewProvider implements PaymentMethodViewProviderInterface
{
    /**
     * @var string Payment method group to filter the payment methods views by.
     *  Leave empty to get all payment methods views.
     */
    private string $paymentMethodGroup = '';

    /**
     * @param iterable<PaymentMethodViewProviderInterface> $innerProviders
     */
    public function __construct(private iterable $innerProviders)
    {
    }

    /**
     * @param string $paymentMethodGroup Payment method group to filter the payment methods views by.
     */
    public function setPaymentMethodGroup(string $paymentMethodGroup): void
    {
        $this->paymentMethodGroup = $paymentMethodGroup;
    }

    #[\Override]
    public function getPaymentMethodViews(array $identifiers): array
    {
        $paymentMethodsViews = [];
        foreach ($this->innerProviders as $paymentMethodProvider) {
            foreach ($paymentMethodProvider->getPaymentMethodViews($identifiers) as $paymentMethodView) {
                if ($this->paymentMethodGroup !== '') {
                    if (!$paymentMethodView instanceof PaymentMethodGroupAwareInterface) {
                        continue;
                    }

                    if (!$paymentMethodView->isApplicableForGroup($this->paymentMethodGroup)) {
                        continue;
                    }
                }

                $paymentMethodsViews[] = $paymentMethodView;
            }
        }

        return $paymentMethodsViews;
    }

    #[\Override]
    public function getPaymentMethodView($identifier): PaymentMethodViewInterface
    {
        foreach ($this->innerProviders as $paymentMethodProvider) {
            if (!$paymentMethodProvider->hasPaymentMethodView($identifier)) {
                continue;
            }

            /**
             * @var PaymentMethodViewInterface|PaymentMethodGroupAwareInterface $paymentMethodView
             */
            $paymentMethodView = $paymentMethodProvider->getPaymentMethodView($identifier);
            if ($this->paymentMethodGroup !== '') {
                if (!$paymentMethodView instanceof PaymentMethodGroupAwareInterface) {
                    continue;
                }

                if (!$paymentMethodView->isApplicableForGroup($this->paymentMethodGroup)) {
                    continue;
                }
            }

            return $paymentMethodView;
        }

        throw new \InvalidArgumentException(
            sprintf(
                'There is no payment method view for "%s" identifier that is applicable for "%s" payment method group.',
                $identifier,
                $this->paymentMethodGroup
            )
        );
    }

    #[\Override]
    public function hasPaymentMethodView($identifier): bool
    {
        foreach ($this->innerProviders as $paymentMethodViewProvider) {
            if (!$paymentMethodViewProvider->hasPaymentMethodView($identifier)) {
                continue;
            }

            /**
             * @var PaymentMethodViewInterface|PaymentMethodGroupAwareInterface $paymentMethodView
             */
            $paymentMethodView = $paymentMethodViewProvider->getPaymentMethodView($identifier);

            if ($this->paymentMethodGroup !== '') {
                if (!$paymentMethodView instanceof PaymentMethodGroupAwareInterface) {
                    continue;
                }

                if (!$paymentMethodView->isApplicableForGroup($this->paymentMethodGroup)) {
                    continue;
                }
            }

            return true;
        }

        return false;
    }
}
