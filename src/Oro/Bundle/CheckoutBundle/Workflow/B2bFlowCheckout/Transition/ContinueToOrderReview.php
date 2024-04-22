<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\Transition;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutPaymentContextProvider;
use Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\ActionGroup\ShippingMethodActionsInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ContinueToOrderReview implements TransitionServiceInterface
{
    public function __construct(
        private ActionExecutor $actionExecutor,
        private ShippingMethodActionsInterface $shippingMethodActions,
        private CheckoutPaymentContextProvider $paymentContextProvider,
        private UrlGeneratorInterface $urlGenerator,
        private TransitionServiceInterface $baseContinueTransition
    ) {
    }

    public function isPreConditionAllowed(WorkflowItem $workflowItem, Collection $errors = null): bool
    {
        /** @var Checkout $checkout */
        $checkout = $workflowItem->getEntity();
        $data = $workflowItem->getData();
        $this->shippingMethodActions->actualizeShippingMethods(
            $checkout,
            $data->offsetGet('line_items_shipping_methods'),
            $data->offsetGet('line_item_groups_shipping_methods')
        );

        if (!$this->baseContinueTransition->isPreConditionAllowed($workflowItem, $errors)) {
            return false;
        }

        if (!$this->shippingMethodActions->hasApplicableShippingRules($checkout, $errors)['hasRules']) {
            return false;
        }

        $paymentContext = $this->getPaymentContext($workflowItem);
        if (!$paymentContext) {
            return false;
        }

        if (!$this->hasApplicablePaymentMethods($paymentContext, $errors)) {
            return false;
        }

        return true;
    }

    public function isConditionAllowed(WorkflowItem $workflowItem, Collection $errors = null): bool
    {
        if (!$this->checkRequest('_wid', 'ajax_checkout')) {
            $errors?->add(['message' => 'oro.checkout.workflow.condition.invalid_request.message']);

            return false;
        }

        /** @var Checkout $checkout */
        $checkout = $workflowItem->getEntity();
        if (!$checkout->getPaymentMethod()) {
            $errors?->add(['message' => 'oro.checkout.workflow.condition.payment_method_was_not_selected.message']);

            return false;
        }

        if (!$this->isPaymentMethodApplicable($checkout, $errors)) {
            return false;
        }

        return true;
    }

    public function execute(WorkflowItem $workflowItem): void
    {
        if (!$workflowItem->getData()->offsetGet('payment_validate')) {
            return;
        }

        /** @var Checkout $checkout */
        $checkout = $workflowItem->getEntity();
        $workflowResult = $workflowItem->getResult();
        $workflowResult->offsetSet('validateAction', PaymentMethodInterface::VALIDATE);

        $workflowResult->offsetSet(
            'successUrl',
            $this->urlGenerator->generate('oro_checkout_frontend_checkout', ['id' => $checkout->getId()])
        );
        $workflowResult->offsetSet(
            'failureUrl',
            $this->urlGenerator->generate(
                'oro_checkout_frontend_checkout',
                [
                    'id' => $checkout->getId(),
                    'transition' => 'payment_error'
                ]
            )
        );

        $isPaymentMethodSupported = $this->actionExecutor->evaluateExpression(
            'payment_method_supports',
            [
                'payment_method' => $checkout->getPaymentMethod(),
                'action' => $workflowResult->offsetGet('validateAction')
            ]
        );
        if ($isPaymentMethodSupported) {
            $paymentValidateResult = $this->actionExecutor->executeAction(
                'payment_validate',
                [
                    'attribute' => null,
                    'object' => $checkout,
                    'paymentMethod' => $checkout->getPaymentMethod(),
                    'transactionOptions' => [
                        'saveForLaterUse' => $workflowItem->getData()->offsetGet('payment_save_for_later'),
                        'successUrl' => $workflowResult->offsetGet('successUrl'),
                        'failureUrl' => $workflowResult->offsetGet('failureUrl'),
                        'additionalData' => $workflowItem->getData()->offsetGet('additional_data'),
                        'checkoutId' => $checkout->getId()
                    ]
                ]
            );
            $workflowResult->offsetSet('responseData', $paymentValidateResult['attribute']);
        }
    }

    private function getPaymentContext(WorkflowItem $workflowItem): ?PaymentContextInterface
    {
        $workflowResult = $workflowItem->getResult();
        $paymentContext = $workflowResult->offsetGet('paymentContext');
        if ($paymentContext) {
            return $paymentContext;
        }

        $paymentContext = $this->paymentContextProvider->getContext($workflowItem->getEntity());
        $workflowResult->offsetSet('paymentContext', $paymentContext);

        return $paymentContext;
    }

    private function hasApplicablePaymentMethods(PaymentContextInterface $paymentContext, ?Collection $errors): bool
    {
        return $this->actionExecutor->evaluateExpression(
            'has_applicable_payment_methods',
            [$paymentContext],
            $errors,
            'oro.checkout.workflow.condition.payment_method_is_not_applicable.message'
        );
    }

    private function isPaymentMethodApplicable(Checkout $checkout, ?Collection $errors): bool
    {
        return $this->actionExecutor->evaluateExpression(
            'payment_method_applicable',
            [
                'context' => $this->paymentContextProvider->getContext($checkout),
                'payment_method' => $checkout->getPaymentMethod()
            ],
            $errors,
            'oro.checkout.workflow.condition.payment_method_was_not_selected.message'
        );
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
}
