<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Method\Provider;

use Oro\Bundle\PaymentBundle\Method\PaymentMethodGroupAwareInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Provides a filtered list of payment methods that are applicable for a specific payment method group.
 */
class PaymentMethodGroupAwareProvider extends AbstractPaymentMethodProvider implements ResetInterface
{
    /**
     * @param PaymentMethodProviderInterface $paymentMethodProvider
     * @param string $paymentMethodGroup Payment method group to filter the payment method by.
     */
    public function __construct(
        private readonly PaymentMethodProviderInterface $paymentMethodProvider,
        private readonly string $paymentMethodGroup
    ) {
        parent::__construct();
    }

    protected function collectMethods(): void
    {
        $paymentMethods = $this->paymentMethodProvider->getPaymentMethods();

        foreach ($paymentMethods as $paymentMethod) {
            if (!$paymentMethod instanceof PaymentMethodGroupAwareInterface) {
                continue;
            }

            if ($paymentMethod->isApplicableForGroup($this->paymentMethodGroup)) {
                $this->addMethod($paymentMethod->getIdentifier(), $paymentMethod);
            }
        }
    }

    #[\Override]
    public function reset(): void
    {
        $this->methods->clear();
    }
}
