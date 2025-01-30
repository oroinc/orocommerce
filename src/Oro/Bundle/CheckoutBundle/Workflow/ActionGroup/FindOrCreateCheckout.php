<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\ActionGroup;

use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\CheckoutBundle\Factory\CheckoutLineItemsFactory;
use Oro\Bundle\CheckoutBundle\Helper\CheckoutCompareHelper;
use Oro\Bundle\CheckoutBundle\Model\CheckoutBySourceCriteriaManipulatorInterface;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\UserBundle\Entity\UserInterface;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Find existing Checkout or create a new one and start checkout workflow.
 */
class FindOrCreateCheckout implements FindOrCreateCheckoutInterface
{
    public function __construct(
        private ActionExecutor $actionExecutor,
        private WorkflowManager $workflowManager,
        private UserCurrencyManager $userCurrencyManager,
        private WebsiteManager $websiteManager,
        private CheckoutLineItemsFactory $checkoutLineItemsFactory,
        private CheckoutCompareHelper $checkoutCompareHelper,
        private TokenStorageInterface $tokenStorage,
        private CheckoutBySourceCriteriaManipulatorInterface $checkoutBySourceCriteriaManipulator
    ) {
    }

    #[\Override]
    public function execute(
        array $sourceCriteria,
        array $checkoutData = [],
        bool $updateData = false,
        bool $forceStartCheckout = false,
        ?string $startTransition = null
    ): array {
        $currentWorkflow = $this->getCurrentWorkflow();
        $currentUser = $this->getCurrentUser();
        $currentCurrency = $this->userCurrencyManager->getUserCurrency();

        $checkout = null;
        $workflowItem = null;
        if (!$forceStartCheckout) {
            $checkout = $this->checkoutBySourceCriteriaManipulator->findCheckout(
                $sourceCriteria,
                $currentUser,
                $currentCurrency,
                $currentWorkflow->getName()
            );
        }

        $currentWebsite = $this->websiteManager->getCurrentWebsite();
        if ($checkout?->getId()) {
            $this->checkoutBySourceCriteriaManipulator->actualizeCheckout(
                $checkout,
                $currentWebsite,
                $sourceCriteria,
                $currentCurrency,
                $checkoutData,
                $updateData
            );
            $checkout = $this->resetCheckoutWorkflowOnChanges($sourceCriteria, $currentCurrency, $checkout);
            $workflowItem = $this->workflowManager->getWorkflowItem($checkout, $currentWorkflow->getName());

            $this->removeCheckoutState($workflowItem, $checkout);
        }

        if (!$checkout?->getId()) {
            $checkout = $this->checkoutBySourceCriteriaManipulator->createCheckout(
                $currentWebsite,
                $sourceCriteria,
                $this->isVisitor() ? null : $currentUser,
                $currentCurrency,
                $checkoutData
            );

            $updateData = true;
            $this->actionExecutor->executeAction(
                'flush_entity',
                [$checkout]
            );

            $workflowItem = $this->workflowManager->startWorkflow(
                $currentWorkflow,
                $checkout,
                $startTransition
            );
        }

        return [
            'checkout' => $checkout,
            'workflowItem' => $workflowItem,
            'updateData' => $updateData
        ];
    }

    private function isVisitor(): bool
    {
        return $this->tokenStorage->getToken() instanceof AnonymousCustomerUserToken;
    }

    private function resetCheckoutWorkflowOnChanges(
        array $sourceCriteria,
        ?string $currentCurrency,
        ?Checkout $checkout
    ): ?Checkout {
        $createObjectResult = $this->actionExecutor->executeAction(
            'create_object',
            ['class' => CheckoutSource::class, 'data' => $sourceCriteria, 'attribute' => null]
        );
        $rawSource = $createObjectResult['attribute'];

        $rawCheckout = new Checkout();
        $rawCheckout->setSource($rawSource);
        $rawCheckout->setCurrency($currentCurrency);
        $rawCheckout->setLineItems(
            $this->checkoutLineItemsFactory->create($rawCheckout->getSource()?->getEntity())
        );

        return $this->checkoutCompareHelper->resetCheckoutIfSourceLineItemsChanged($checkout, $rawCheckout);
    }

    private function getCurrentWorkflow(): Workflow
    {
        $currentWorkflow = $this->workflowManager->getAvailableWorkflowByRecordGroup(
            Checkout::class,
            'b2b_checkout_flow'
        );
        if (null === $currentWorkflow) {
            throw new \RuntimeException('Active checkout workflow was not found');
        }

        return $currentWorkflow;
    }

    private function getCurrentUser(): ?UserInterface
    {
        $userResult = $this->actionExecutor->executeAction(
            'get_active_user_or_null',
            ['attribute' => null]
        );

        return $userResult['attribute'];
    }

    private function removeCheckoutState(WorkflowItem $workflowItem, ?Checkout $checkout): void
    {
        if (null === $checkout) {
            return;
        }

        $stateToken = $workflowItem->getData()->offsetGet('state_token');
        if (!$stateToken) {
            return;
        }

        $this->actionExecutor->executeAction(
            'delete_checkout_state',
            [
                'entity' => $checkout,
                'token' => $stateToken
            ]
        );
        $this->actionExecutor->executeAction(
            'flush_entity',
            [$checkout]
        );
    }
}
