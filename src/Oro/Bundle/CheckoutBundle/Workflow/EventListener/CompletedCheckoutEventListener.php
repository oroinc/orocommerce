<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\EventListener;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Helper\CheckoutWorkflowHelper;
use Oro\Bundle\WorkflowBundle\Event\Transition\PreAnnounceEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Notifies the buyer about checkout completion and provides a link to the associated Order view page.
 * Used in cases when Checkout is completed within a separate session and then accessed by a direct URL.
 */
class CompletedCheckoutEventListener
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function onPreAnnounce(PreAnnounceEvent $event): void
    {
        $errors = $event->getErrors();
        if (null === $errors) {
            return;
        }

        if (!CheckoutWorkflowHelper::isCheckoutWorkflow($event->getWorkflowItem())) {
            return;
        }

        if (empty($event->getTransition()->getFrontendOptions()['is_checkout_continue'])) {
            return;
        }

        /** @var Checkout $checkout */
        $checkout = $event->getWorkflowItem()->getEntity();
        if (!$checkout->isCompleted()) {
            return;
        }

        $order = $checkout->getOrder();
        if (null === $order) {
            return;
        }

        $errors->add(
            [
                'message' => 'oro.checkout.workflow.condition.completed_workflow.message',
                'parameters' => [
                    '%orderViewLink%' => $this->urlGenerator->generate(
                        'oro_order_frontend_view',
                        ['id' => $order->getId()]
                    ),
                    '%orderIdentifier%' => $order->getIdentifier()
                ]
            ]
        );
    }
}
