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

        if ($this->isLineItemsCheckNeeded($checkout, $workflowItem, $request)) {
            $this->checkLineItemsCount($checkout, $request);
        }
    }

    private function checkLineItemsCount(Checkout $checkout, Request $request): void
    {
        $allOrderLineItemsCount = $this->lineItemsManager->getData($checkout, true)->count();

        if ($allOrderLineItemsCount) {
            $orderLineItemsCount = $this->lineItemsManager->getData($checkout)->count();
            if ($allOrderLineItemsCount !== $orderLineItemsCount) {
                $rfpOrderLineItems = $this->lineItemsManager
                    ->getData($checkout, true, 'oro_rfp.frontend_product_visibility');
                $message = $rfpOrderLineItems->isEmpty()
                    ? 'oro.checkout.order.line_items.line_item_has_no_price_not_allow_rfp.message'
                    : 'oro.checkout.order.line_items.line_item_has_no_price_allow_rfp.message';
                $request->getSession()->getFlashBag()->add('warning', $message);

                return;
            }
        }

        if ($allOrderLineItemsCount !== $checkout->getLineItems()?->count()) {
            $request->getSession()->getFlashBag()->add(
                'warning',
                'oro.checkout.order.line_items.line_item_has_no_price_not_allow_rfp.message'
            );
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
