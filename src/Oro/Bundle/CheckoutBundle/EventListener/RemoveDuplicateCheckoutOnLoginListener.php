<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Event\LoginOnCheckoutEvent;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

/**
 * Removes duplicate checkouts on guest checkout log in.
 */
class RemoveDuplicateCheckoutOnLoginListener
{
    public function __construct(
        private WorkflowManager $workflowManager,
        private UserCurrencyManager $userCurrencyManager,
        private ManagerRegistry $registry,
    ) {
    }

    public function onCheckoutLogin(LoginOnCheckoutEvent $event): void
    {
        if (!$this->isApplicable($event)) {
            return;
        }

        $workflow = $this->workflowManager->getAvailableWorkflowByRecordGroup(Checkout::class, 'b2b_checkout_flow');
        if (!$workflow) {
            return;
        }

        $em = $this->registry->getManager();
        $doFlush = false;

        $duplicateCheckouts = $this->getDuplicateCheckouts($event->getCheckoutEntity(), $workflow->getName());
        foreach ($duplicateCheckouts as $checkout) {
            $em->remove($checkout);
            $doFlush = true;
        }

        if ($doFlush) {
            $em->flush();
        }
    }

    private function isApplicable(LoginOnCheckoutEvent $event): bool
    {
        return $event->getCheckoutEntity() &&
            $event->getCheckoutEntity()->getSourceEntity() &&
            $event->getCheckoutEntity()->getCustomerUser();
    }

    private function getDuplicateCheckouts(Checkout $checkout, string $workflowName): array
    {
        return $this->registry->getRepository(Checkout::class)
            ->findDuplicateCheckouts(
                $checkout->getCustomerUser(),
                ['shoppingList' => $checkout->getSourceEntity()],
                $workflowName,
                [$checkout->getId()],
                $this->userCurrencyManager->getUserCurrency()
            );
    }
}
