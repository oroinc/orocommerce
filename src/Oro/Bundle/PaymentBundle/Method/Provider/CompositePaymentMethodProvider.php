<?php

namespace Oro\Bundle\PaymentBundle\Method\Provider;

use Oro\Bundle\PaymentBundle\Method\PaymentMethodGroupAwareInterface;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;

/**
 * The registry of payment method providers.
 */
class CompositePaymentMethodProvider implements PaymentMethodProviderInterface
{
    /**
     * @var string Payment method group to filter the payment methods by.
     */
    private string $paymentMethodGroup = '';

    /**
     * @param iterable<PaymentMethodProviderInterface> $innerProviders
     */
    public function __construct(private iterable $innerProviders)
    {
    }

    /**
     * @param string $paymentMethodGroup Payment method group to filter the payment methods by.
     */
    public function setPaymentMethodGroup(string $paymentMethodGroup): void
    {
        $this->paymentMethodGroup = $paymentMethodGroup;
    }

    #[\Override]
    public function getPaymentMethods(): array
    {
        $paymentMethods = [];
        foreach ($this->innerProviders as $paymentMethodProvider) {
            foreach ($paymentMethodProvider->getPaymentMethods() as $identifier => $paymentMethod) {
                if ($this->paymentMethodGroup !== '' && !$paymentMethod instanceof PaymentMethodGroupAwareInterface) {
                    continue;
                }

                if ($paymentMethod instanceof PaymentMethodGroupAwareInterface &&
                    !$paymentMethod->isApplicableForGroup($this->paymentMethodGroup)) {
                    continue;
                }

                $paymentMethods[$identifier] = $paymentMethod;
            }
        }

        return $paymentMethods;
    }

    #[\Override]
    public function getPaymentMethod($identifier): PaymentMethodInterface
    {
        foreach ($this->innerProviders as $paymentMethodProvider) {
            if (!$paymentMethodProvider->hasPaymentMethod($identifier)) {
                continue;
            }

            /** @var PaymentMethodInterface|PaymentMethodGroupAwareInterface $paymentMethod $paymentMethod */
            $paymentMethod = $paymentMethodProvider->getPaymentMethod($identifier);
            if ($this->paymentMethodGroup !== '' && !$paymentMethod instanceof PaymentMethodGroupAwareInterface) {
                continue;
            }

            if ($paymentMethod instanceof PaymentMethodGroupAwareInterface &&
                !$paymentMethod->isApplicableForGroup($this->paymentMethodGroup)) {
                continue;
            }

            return $paymentMethod;
        }

        throw new \InvalidArgumentException(
            sprintf(
                'There is no payment method for "%s" identifier that is applicable for "%s" payment method group.',
                $identifier,
                $this->paymentMethodGroup
            )
        );
    }

    #[\Override]
    public function hasPaymentMethod($identifier): bool
    {
        foreach ($this->innerProviders as $paymentMethodProvider) {
            if (!$paymentMethodProvider->hasPaymentMethod($identifier)) {
                continue;
            }

            /** @var PaymentMethodInterface|PaymentMethodGroupAwareInterface $paymentMethod $paymentMethod */
            $paymentMethod = $paymentMethodProvider->getPaymentMethod($identifier);
            if ($this->paymentMethodGroup !== '' && !$paymentMethod instanceof PaymentMethodGroupAwareInterface) {
                continue;
            }

            if ($paymentMethod instanceof PaymentMethodGroupAwareInterface &&
                !$paymentMethod->isApplicableForGroup($this->paymentMethodGroup)) {
                continue;
            }

            return true;
        }

        return false;
    }
}
