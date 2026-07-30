<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\EventListener;

use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Event\CheckoutRequestEvent;
use Oro\Bundle\CheckoutBundle\Helper\CheckoutWorkflowHelper;
use Oro\Bundle\CheckoutBundle\Layout\DataProvider\TransitionProvider;
use Oro\Bundle\CheckoutBundle\Model\TransitionData;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Check that all line items may be added to the checkout, show warning otherwise.
 */
class CheckLineItemsCount
{
    public function __construct(
        private CheckoutWorkflowHelper $checkoutWorkflowHelper,
        private TransitionProvider $transitionProvider,
        private CheckoutLineItemsManager $lineItemsManager,
        private TranslatorInterface $translator
    ) {
    }

    public function onCheckoutRequest(CheckoutRequestEvent $event): void
    {
        $request = $event->getRequest();
        $checkout = $event->getCheckout();
        $workflowItem = $this->checkoutWorkflowHelper->getWorkflowItem($checkout);
        if (!$workflowItem) {
            return;
        }

        if ($this->isLineItemsCheckNeeded($checkout, $workflowItem, $request)) {
            $this->checkLineItemsCount($checkout, $request);
        }
    }

    private function checkLineItemsCount(Checkout $checkout, Request $request): void
    {
        $flashBag = $request->getSession()->getFlashBag();
        $reasons = $this->lineItemsManager->getDataWithReason($checkout)['skippedReasons'];

        if (\in_array(CheckoutLineItemsManager::REASON_CURRENCY_MISMATCH, $reasons, true)) {
            $flashBag->add('warning', 'oro.checkout.order.line_items.different_currency.message');
        }

        $noPrice = \in_array(CheckoutLineItemsManager::REASON_NO_PRICE, $reasons, true)
            || \in_array(CheckoutLineItemsManager::REASON_UNSUPPORTED_STATUS, $reasons, true);

        if ($noPrice) {
            $rfpOrderLineItems = $this->lineItemsManager
                ->getData($checkout, true, 'oro_rfp.frontend_product_visibility');
            $flashBag->add('warning', $rfpOrderLineItems->isEmpty()
                ? 'oro.checkout.order.line_items.line_item_has_no_price_not_allow_rfp.message'
                : 'oro.checkout.order.line_items.line_item_has_no_price_allow_rfp.message');
        }

        // Line items lost before filtering (e.g. removed during conversion) keep the generic warning.
        if (!$reasons) {
            $countAllData = $this->lineItemsManager->getData($checkout, true)->count();
            $checkoutLineItemsCount = $checkout->getLineItems()?->count();
            if ($countAllData !== $checkoutLineItemsCount) {
                $flashBag->add(
                    'warning',
                    'oro.checkout.order.line_items.line_item_has_no_price_not_allow_rfp.message'
                );
            }
        }
    }

    private function isLineItemsCheckNeeded(Checkout $checkout, WorkflowItem $workflowItem, Request $request): bool
    {
        if (!$checkout->getId()) {
            return false;
        }

        if ($request->isXmlHttpRequest()) {
            return false;
        }

        $continueTransition = $this->transitionProvider->getContinueTransition($workflowItem);
        if (!$continueTransition) {
            return false;
        }

        $frontendOptions = $continueTransition->getTransition()->getFrontendOptions();
        if (!\array_key_exists('is_checkout_show_errors', $frontendOptions)) {
            return false;
        }

        $this->addTransitionErrors($continueTransition, $request);

        $errors = $continueTransition->getErrors();
        if (!$errors->isEmpty()) {
            return false;
        }

        return true;
    }

    private function addTransitionErrors(TransitionData $continueTransition, Request $request): void
    {
        $errors = $continueTransition->getErrors();
        foreach ($errors as $error) {
            $request->getSession()->getFlashBag()->add(
                'error',
                $this->translator->trans((string) ($error['message'] ?? ''), (array) ($error['parameters'] ?? []))
            );
        }
    }
}
