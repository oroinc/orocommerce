<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\ActionGroup;

use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;

/**
 * Checkout workflow payment method-related actions.
 */
class PaymentMethodActions implements PaymentMethodActionsInterface
{
    public function __construct(
        private ActionExecutor $actionExecutor
    ) {
    }

    public function validate(
        Checkout $checkout,
        string $successUrl,
        string $failureUrl,
        ?string $additionalData,
        ?bool $saveForLaterUse
    ): array {
        if (!$this->isPaymentMethodSupportsValidate($checkout)) {
            return [];
        }

        $paymentValidateResult = $this->actionExecutor->executeAction(
            'payment_validate',
            [
                'attribute' => null,
                'object' => $checkout,
                'paymentMethod' => $checkout->getPaymentMethod(),
                'transactionOptions' => [
                    'saveForLaterUse' => $saveForLaterUse,
                    'successUrl' => $successUrl,
                    'failureUrl' => $failureUrl,
                    'additionalData' => $additionalData,
                    'checkoutId' => $checkout->getId()
                ]
            ]
        );

        return $paymentValidateResult['attribute'];
    }

    public function isPaymentMethodSupportsValidate(Checkout $checkout): bool
    {
        if (!$checkout->getPaymentMethod()) {
            return false;
        }

        return $this->actionExecutor->evaluateExpression(
            'payment_method_supports',
            [
                'payment_method' => $checkout->getPaymentMethod(),
                'action' => PaymentMethodInterface::VALIDATE
            ]
        );
    }
}
