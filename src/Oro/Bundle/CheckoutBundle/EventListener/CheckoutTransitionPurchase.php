<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Oro\Bundle\CheckoutBundle\Event\CheckoutTransitionAfterEvent;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Model\WorkflowResult;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Completes the checkout if the payment was successful (only for authorized payments).
 *
 * This is necessary because an order that has already been paid for cannot be changed in any way.
 * Therefore, need to guarantee that the checkout is over and the workflow is closed.
 */
class CheckoutTransitionPurchase implements EventSubscriberInterface
{
    /**
     * By default, use 'finish_checkout' as the final transition.
     */
    public function __construct(
        private WorkflowManager $manager,
        private TokenStorageInterface $tokenStorage,
        private string $transitionName = 'finish_checkout'
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [CheckoutTransitionAfterEvent::class => 'onAfter'];
    }

    public function onAfter(CheckoutTransitionAfterEvent $event): void
    {
        if (!$this->isTransitionAllowedForCurrentUser()) {
            return;
        }

        $workflowItem = $event->getWorkflowItem();
        if (!$this->isSuccessfulPayment($workflowItem->getResult())) {
            return;
        }

        $this->manager->transitIfAllowed($workflowItem, $this->transitionName);
    }

    /**
     * Note that we take as a basis the response that comes back from the payment integration, and the successful
     * variable will be true only if the payment has passed and the money has been debited from the account.
     */
    private function isSuccessfulPayment(WorkflowResult $workflowResult): bool
    {
        if ($workflowResult->has('responseData')) {
            $response = $workflowResult->get('responseData');

            return is_array($response) && isset($response['successful']) && true === $response['successful'];
        }

        return false;
    }

    /**
     * Ignore the anonymous user because orders are not available after creation.
     */
    private function isTransitionAllowedForCurrentUser(): bool
    {
        return !$this->tokenStorage->getToken() instanceof AnonymousCustomerUserToken;
    }
}
