<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\Transition;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Workflow\BaseTransition\ContinueTransition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;

/**
 * B2bCheckout workflow transition verify_payment logic implementation.
 */
class VerifyPayment extends ContinueTransition
{
    public function __construct(
        private ActionExecutor $actionExecutor
    ) {
    }

    #[\Override]
    public function isPreConditionAllowed(WorkflowItem $workflowItem, ?Collection $errors = null): bool
    {
        /** @var Checkout $checkout */
        $checkout = $workflowItem->getEntity();

        if ($checkout->isCompleted()) {
            return false;
        }

        if ($workflowItem->getData()->offsetGet('payment_in_progress')) {
            return false;
        }

        if (
            $this->checkRequest('_wid', 'ajax_checkout')
            && $this->checkRequest('transition', 'continue_to_order_review')
        ) {
            return false;
        }

        $paymentMethod = $checkout->getPaymentMethod();
        if (!$paymentMethod) {
            return false;
        }
        if (!$this->isPaymentRedirectRequired($paymentMethod)) {
            return false;
        }

        return $this->isValidationPassed($checkout, 'checkout_verify_payment', $errors);
    }

    private function checkRequest(string $key, string $value): bool
    {
        return $this->actionExecutor->evaluateExpression(
            'check_request',
            [
                'is_ajax' => true,
                'expected_key' => $key,
                'expected_value' => $value
            ]
        );
    }

    private function isPaymentRedirectRequired(string $paymentMethod): bool
    {
        return $this->actionExecutor->evaluateExpression(
            'require_payment_redirect',
            ['payment_method' => $paymentMethod]
        );
    }
}
