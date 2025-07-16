<?php

namespace Oro\Bundle\CheckoutBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutRepository;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Layout\DataProvider\CurrentUserProvider;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

/**
 * Allows getting a checkout using the current currency, customer user and shopping list.
 */
class ShoppingListCheckoutProvider
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private UserCurrencyManager $userCurrencyManager,
        private CurrentUserProvider $currentUserProvider,
        private WorkflowManager $workflowManager,
    ) {
    }

    public function getCheckout(ShoppingList $shoppingList): ?Checkout
    {
        $customerUser = $shoppingList->getCustomerUser() ?? $this->currentUserProvider->getCurrentUser();
        if (!$customerUser instanceof CustomerUser) {
            return null;
        }

        $currentWorkflow = $this->workflowManager->getAvailableWorkflowByRecordGroup(
            Checkout::class,
            'b2b_checkout_flow'
        );
        if (!$currentWorkflow) {
            return null;
        }

        $currency = $this->userCurrencyManager->getUserCurrency();

        return $this->getCheckoutRepository()->findCheckoutByCustomerUserAndSourceCriteriaWithCurrency(
            $customerUser,
            ['shoppingList' => $shoppingList],
            $currentWorkflow->getName(),
            $currency
        );
    }

    private function getCheckoutRepository(): CheckoutRepository
    {
        return $this->managerRegistry->getManager()->getRepository(Checkout::class);
    }
}
