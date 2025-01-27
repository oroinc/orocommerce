<?php

namespace Oro\Bundle\CheckoutBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutRepository;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Layout\DataProvider\CurrentUserProvider;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

/**
 * Allows getting a checkout using the current currency, customer user and shopping list.
 */
class ShoppingListCheckoutProvider
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private UserCurrencyManager $userCurrencyManager,
        private CurrentUserProvider $currentUserProvider,
    ) {
    }

    public function getCheckout(ShoppingList $shoppingList): ?Checkout
    {
        $customerUser = $this->currentUserProvider->getCurrentUser();
        if (!$customerUser instanceof CustomerUser) {
            return null;
        }

        $workflowDefinitionName = $this->getWorkflowDefinitionName();
        if (!$workflowDefinitionName) {
            return null;
        }

        $currency = $this->userCurrencyManager->getUserCurrency();

        return $this->getCheckoutRepository()->findCheckoutByCustomerUserAndSourceCriteriaWithCurrency(
            $customerUser,
            ['shoppingList' => $shoppingList],
            $workflowDefinitionName,
            $currency
        );
    }

    private function getWorkflowDefinitionName(): ?string
    {
        $workflowDefinitionRepository = $this->getWorkflowDefinitionRepository();
        $definitions = array_filter(
            $workflowDefinitionRepository->findActiveForRelatedEntity(Checkout::class),
            function (WorkflowDefinition $definition) {
                return in_array('b2b_checkout_flow', $definition->getExclusiveActiveGroups(), true);
            }
        );

        $workflowDefinition = reset($definitions);

        return $workflowDefinition ? $workflowDefinition->getName() : null;
    }

    private function getWorkflowDefinitionRepository(): WorkflowDefinitionRepository
    {
        return $this->managerRegistry->getRepository(WorkflowDefinition::class);
    }

    private function getCheckoutRepository(): CheckoutRepository
    {
        return $this->managerRegistry->getManager()->getRepository(Checkout::class);
    }
}
