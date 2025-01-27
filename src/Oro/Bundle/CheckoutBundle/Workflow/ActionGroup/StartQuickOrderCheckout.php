<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\ActionGroup;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutRepository;
use Oro\Bundle\CheckoutBundle\Provider\OrderLimitFormattedProviderInterface;
use Oro\Bundle\CheckoutBundle\Provider\OrderLimitProviderInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Start checkout from quick order form.
 */
class StartQuickOrderCheckout implements StartQuickOrderCheckoutInterface
{
    public function __construct(
        private ActionExecutor $actionExecutor,
        private UserCurrencyManager $userCurrencyManager,
        private CheckoutRepository $checkoutRepository,
        private ManagerRegistry $registry,
        private StartShoppingListCheckoutInterface $startShoppingListCheckout,
        private WorkflowManager $workflowManager,
        private OrderLimitProviderInterface $shoppingListLimitProvider,
        private OrderLimitFormattedProviderInterface $shoppingListLimitFormattedProvider,
        private TranslatorInterface $translator
    ) {
    }

    #[\Override]
    public function execute(
        ShoppingList $shoppingList,
        ?string $transitionName = null
    ): array {
        $currentUser = $this->getCurrentUser();

        if ($currentUser && $currentUser->isGuest()) {
            // Guest customer user will be not null for guest checkout when there is another guest checkout in
            // the DB started by the same visitor. At first run customer user is null,
            // and it is created when first checkout step is passed
            $this->removeExistingCheckout($currentUser, $shoppingList);
        }

        if (!empty($errors = $this->assertOrderLimits($shoppingList))) {
            return [
                'errors' => $errors
            ];
        }

        $startResult = $this->startShoppingListCheckout->execute(
            shoppingList: $shoppingList,
            showErrors: true
        );

        $checkout = $startResult['checkout'] ?? null;
        $workflowItem = $startResult['workflowItem'] ?? null;
        if (empty($startResult['errors'])
            && $transitionName
            && $checkout instanceof Checkout
            && $workflowItem instanceof WorkflowItem
        ) {
            // Transit workflow is called here because internally it will fetch real WorkflowItem instead of stub.
            $this->actionExecutor->executeAction(
                'transit_workflow',
                [
                    'entity' => $checkout,
                    'workflow' => $startResult['workflowItem']->getWorkflowName(),
                    'transition' => $transitionName
                ]
            );
        }

        return $startResult;
    }

    private function getCurrentUser(): ?CustomerUser
    {
        $userResult = $this->actionExecutor->executeAction(
            'get_active_user_or_null',
            ['attribute' => null]
        );

        return $userResult['attribute'] ?? null;
    }

    private function removeExistingCheckout(
        CustomerUser $currentUser,
        ShoppingList $shoppingList
    ): void {
        $currentWorkflow = $this->workflowManager->getAvailableWorkflowByRecordGroup(
            Checkout::class,
            'b2b_checkout_flow'
        );

        if (!$currentWorkflow) {
            return;
        }

        $currentCurrency = $this->userCurrencyManager->getUserCurrency();
        $checkout = $this->checkoutRepository->findCheckoutByCustomerUserAndSourceCriteriaWithCurrency(
            $currentUser,
            ['shoppingList' => $shoppingList],
            $currentWorkflow->getName(),
            $currentCurrency
        );

        if (!$checkout) {
            return;
        }

        $em = $this->registry->getManagerForClass(Checkout::class);
        $em->remove($checkout);
        $em->flush($checkout);
    }

    /**
     * @param ShoppingList $shoppingList
     * @return array<int,string>
     */
    private function assertOrderLimits(ShoppingList $shoppingList): array
    {
        $errors = [];

        if (!$this->shoppingListLimitProvider->isMinimumOrderAmountMet($shoppingList)) {
            $errors[] = $this->translator->trans(
                'oro.checkout.frontend.checkout.order_limits.minimum_order_amount_flash',
                [
                    '%amount%' => $this->shoppingListLimitFormattedProvider->getMinimumOrderAmountFormatted(),
                    '%difference%' =>
                        $this->shoppingListLimitFormattedProvider->getMinimumOrderAmountDifferenceFormatted(
                            $shoppingList
                        ),
                ]
            );
        }

        if (!$this->shoppingListLimitProvider->isMaximumOrderAmountMet($shoppingList)) {
            $errors[] = $this->translator->trans(
                'oro.checkout.frontend.checkout.order_limits.maximum_order_amount_flash',
                [
                    '%amount%' => $this->shoppingListLimitFormattedProvider->getMaximumOrderAmountFormatted(),
                    '%difference%' =>
                        $this->shoppingListLimitFormattedProvider->getMaximumOrderAmountDifferenceFormatted(
                            $shoppingList
                        ),
                ]
            );
        }

        return $errors;
    }
}
