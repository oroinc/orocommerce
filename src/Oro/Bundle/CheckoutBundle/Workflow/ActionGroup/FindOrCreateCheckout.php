<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\ActionGroup;

use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutRepository;
use Oro\Bundle\CheckoutBundle\Factory\CheckoutLineItemsFactory;
use Oro\Bundle\CheckoutBundle\Helper\CheckoutCompareHelper;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\UserBundle\Entity\UserInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;
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
        private CheckoutRepository $checkoutRepository,
        private WebsiteManager $websiteManager,
        private ActualizeCheckoutInterface $actualizeCheckout,
        private CheckoutLineItemsFactory $checkoutLineItemsFactory,
        private CheckoutCompareHelper $checkoutCompareHelper,
        private TokenStorageInterface $tokenStorage,
    ) {
    }

    public function execute(
        array $sourceCriteria,
        array $checkoutData = [],
        bool $updateData = false,
        bool $forceStartCheckout = false,
        string $startTransition = null
    ): array {
        $currentWorkflow = $this->getCurrentWorkflow();
        $currentUser = $this->getCurrentUser();
        $currentCurrency = $this->userCurrencyManager->getUserCurrency();

        $checkout = null;
        $workflowItem = null;
        if (!$forceStartCheckout) {
            $checkout = $this->findExistingCheckout(
                $currentUser,
                $sourceCriteria,
                $currentWorkflow,
                $currentCurrency
            );
        }

        $currentWebsite = $this->websiteManager->getCurrentWebsite();
        if ($checkout?->getId()) {
            $this->actualizeCheckout->execute(
                $checkout,
                $sourceCriteria,
                $currentWebsite,
                $updateData,
                $checkoutData
            );
            $checkout = $this->resetCheckoutOnChanges($sourceCriteria, $currentCurrency, $checkout);
            $workflowItem = $this->workflowManager->getWorkflowItem($checkout, $currentWorkflow->getName());

            $this->removeCheckoutState($workflowItem, $checkout);
        }

        if (!$checkout?->getId()) {
            $checkout = $this->createCheckout($sourceCriteria, $currentWebsite, $currentUser, $checkoutData);

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
        $token = $this->tokenStorage->getToken();

        return $token instanceof AnonymousCustomerUserToken;
    }

    protected function createCheckout(
        array $sourceCriteria,
        ?Website $currentWebsite,
        ?UserInterface $currentUser,
        array $checkoutData
    ): Checkout {
        $createEntityResult = $this->actionExecutor->executeAction(
            'create_entity',
            ['class' => CheckoutSource::class, 'data' => $sourceCriteria, 'attribute' => null]
        );
        $source = $createEntityResult['attribute'];

        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $checkout = new Checkout();
        $checkout->setSource($source);
        $checkout->setWebsite($currentWebsite);
        $checkout->setCreatedAt($now);
        $checkout->setUpdatedAt($now);
        if (!$this->isVisitor()) {
            $checkout->setCustomerUser($currentUser);
        }
        $this->actualizeCheckout->execute(
            $checkout,
            $sourceCriteria,
            $currentWebsite,
            true,
            $checkoutData
        );

        return $checkout;
    }

    protected function resetCheckoutOnChanges(
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

    protected function findExistingCheckout(
        $currentUser,
        array $sourceCriteria,
        Workflow $currentWorkflow,
        ?string $currentCurrency
    ): ?Checkout {
        if ($currentUser) {
            return $this->checkoutRepository->findCheckoutByCustomerUserAndSourceCriteriaWithCurrency(
                $currentUser,
                $sourceCriteria,
                $currentWorkflow->getName(),
                $currentCurrency
            );
        }

        return $this->checkoutRepository->findCheckoutBySourceCriteriaWithCurrency(
            $sourceCriteria,
            $currentWorkflow->getName(),
            $currentCurrency
        );
    }

    protected function getCurrentWorkflow(): Workflow
    {
        $currentWorkflow = $this->workflowManager->getAvailableWorkflowByRecordGroup(
            Checkout::class,
            'b2b_checkout_flow'
        );

        if (!$currentWorkflow) {
            throw new \RuntimeException('Active checkout workflow was not found');
        }

        return $currentWorkflow;
    }

    protected function getCurrentUser(): ?UserInterface
    {
        $userResult = $this->actionExecutor->executeAction(
            'get_active_user_or_null',
            ['attribute' => null]
        );

        return $userResult['attribute'];
    }

    protected function removeCheckoutState(WorkflowItem $workflowItem, ?Checkout $checkout): void
    {
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
