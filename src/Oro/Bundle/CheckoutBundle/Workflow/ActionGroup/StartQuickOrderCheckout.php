<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\ActionGroup;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutRepository;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

class StartQuickOrderCheckout implements StartQuickOrderCheckoutInterface
{
    public function __construct(
        private ActionExecutor $actionExecutor,
        private UserCurrencyManager $userCurrencyManager,
        private CheckoutRepository $checkoutRepository,
        private ManagerRegistry $registry,
        private StartShoppingListCheckoutInterface $startShoppingListCheckout,
        private WorkflowManager $workflowManager
    ) {
    }

    public function execute(
        ShoppingList $shoppingList,
        ?string $transitionName = null
    ): array {
        $currentUser = $this->getCurrentUser();

        if ($currentUser && $currentUser->isGuest()) {
            $currentCurrency = $this->userCurrencyManager->getUserCurrency();
            $checkout = $this->checkoutRepository->findCheckoutByCustomerUserAndSourceCriteriaWithCurrency(
                $currentUser,
                ['shoppingList' => $shoppingList],
                'b2b_flow_checkout',
                $currentCurrency
            );

            if ($checkout) {
                $em = $this->registry->getManagerForClass(Checkout::class);
                $em->remove($checkout);
                $em->flush($checkout);
                $em->refresh($checkout);
            }
        }

        $startResult = $this->startShoppingListCheckout->execute(
            shoppingList: $shoppingList,
            showErrors: true
        );

        $checkout = $startResult['checkout'];
        if (empty($startResult['errors']) && $checkout && $transitionName) {
            // TODO: Check if we can use workflow name from workflow item
            $currentWorkflow = $this->workflowManager->getAvailableWorkflowByRecordGroup(
                Checkout::class,
                'b2b_checkout_flow'
            );

            // Transit workflow is called here because internally it will fetch real WorkflowItem instead of stub.
            $this->actionExecutor->executeAction(
                'transit_workflow',
                [
                    'entity' => $checkout,
                    'workflow' => $currentWorkflow->getName(),
                    'transition' => $transitionName
                ]
            );
        }

        return $startResult;
    }

    protected function getCurrentUser(): ?CustomerUser
    {
        $userResult = $this->actionExecutor->executeAction(
            'get_active_user_or_null',
            ['attribute' => null]
        );

        return $userResult['attribute'];
    }
}
