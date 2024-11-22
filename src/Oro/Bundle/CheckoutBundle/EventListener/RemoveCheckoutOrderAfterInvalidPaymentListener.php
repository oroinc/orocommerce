<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Event\CheckoutTransitionBeforeEvent;
use Oro\Bundle\OrderBundle\Entity\Order;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Remove the order before payment, given that the previous payment was unsuccessful
 * (we use the uuid from checkout and order as an indicator that the customer tried to make a payment but for
 * technical reasons, this attempt failed).
 *
 * By default, the order removed if the customer refused to pay and returned to the checkout using the
 * correct url 'payment_error', in all other cases, we assume that the payment failed or was not confirmed and
 * the user could have changed the order data that affected the pricing.
 */
class RemoveCheckoutOrderAfterInvalidPaymentListener implements EventSubscriberInterface
{
    private const ACTIVE_GROUP = 'b2b_checkout_flow';
    private array $transitionNames = [];

    public function __construct(private ManagerRegistry $managerRegistry)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [CheckoutTransitionBeforeEvent::class => 'onBeforeOrderCreate'];
    }

    public function addTransitionName(string $transitionName): self
    {
        $this->transitionNames[$transitionName] = true;

        return $this;
    }

    public function onBeforeOrderCreate(CheckoutTransitionBeforeEvent $event): void
    {
        $workflowItem = $event->getWorkflowItem();
        $recordGroups = $workflowItem->getDefinition()->getExclusiveRecordGroups();
        if (!in_array(self::ACTIVE_GROUP, $recordGroups, true)) {
            return;
        }

        $transition = $event->getTransition()->getName();
        if (empty($this->transitionNames[$transition])) {
            return;
        }

        /** @var Checkout $checkout */
        $checkout = $workflowItem->getEntity();
        /**
         * $checkout->isCompleted() - indicates that the payment is completed and confirmed by the integration,
         * so it is no longer possible to delete or change the order.
         */
        if ($checkout->isCompleted()) {
            return;
        }

        $entityManager = $this->managerRegistry->getManagerForClass(Order::class);
        $order = $entityManager->getRepository(Order::class)->findOneBy(['uuid' => $checkout->getUuid()]);
        if ($order) {
            $workflowItem->getData()->remove('order');
            $entityManager->remove($order);
            $entityManager->flush($order);
        }
    }
}
