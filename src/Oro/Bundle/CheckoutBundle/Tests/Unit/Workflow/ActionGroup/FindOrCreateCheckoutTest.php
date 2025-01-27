<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\ActionGroup;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\CheckoutBundle\Factory\CheckoutLineItemsFactory;
use Oro\Bundle\CheckoutBundle\Helper\CheckoutCompareHelper;
use Oro\Bundle\CheckoutBundle\Model\CheckoutBySourceCriteriaManipulatorInterface;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\FindOrCreateCheckout;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationToken;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Component\Checkout\Entity\CheckoutSourceEntityInterface;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class FindOrCreateCheckoutTest extends TestCase
{
    private ActionExecutor|MockObject $actionExecutor;
    private WorkflowManager|MockObject $workflowManager;
    private UserCurrencyManager|MockObject $userCurrencyManager;
    private WebsiteManager|MockObject $websiteManager;
    private CheckoutLineItemsFactory|MockObject $checkoutLineItemsFactory;
    private CheckoutCompareHelper|MockObject $checkoutCompareHelper;
    private TokenStorageInterface|MockObject $tokenStorage;
    private CheckoutBySourceCriteriaManipulatorInterface|MockObject $checkoutBySourceCriteriaManipulator;

    private FindOrCreateCheckout $findOrCreateCheckout;

    #[\Override]
    protected function setUp(): void
    {
        $this->actionExecutor = $this->createMock(ActionExecutor::class);
        $this->workflowManager = $this->createMock(WorkflowManager::class);
        $this->userCurrencyManager = $this->createMock(UserCurrencyManager::class);
        $this->websiteManager = $this->createMock(WebsiteManager::class);
        $this->checkoutLineItemsFactory = $this->createMock(CheckoutLineItemsFactory::class);
        $this->checkoutCompareHelper = $this->createMock(CheckoutCompareHelper::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->checkoutBySourceCriteriaManipulator = $this->createMock(
            CheckoutBySourceCriteriaManipulatorInterface::class
        );

        $this->findOrCreateCheckout = new FindOrCreateCheckout(
            $this->actionExecutor,
            $this->workflowManager,
            $this->userCurrencyManager,
            $this->websiteManager,
            $this->checkoutLineItemsFactory,
            $this->checkoutCompareHelper,
            $this->tokenStorage,
            $this->checkoutBySourceCriteriaManipulator
        );
    }

    private function getCheckout(int $id): Checkout
    {
        $checkout = new Checkout();
        ReflectionUtil::setId($checkout, $id);

        return $checkout;
    }

    private function getCheckoutSource(CheckoutSourceEntityInterface $source): CheckoutSource
    {
        $checkoutSource = $this->createMock(CheckoutSource::class);
        $checkoutSource->expects(self::any())
            ->method('getEntity')
            ->willReturn($source);

        return $checkoutSource;
    }

    private function getWorkflow(): Workflow
    {
        $workflow = $this->createMock(Workflow::class);
        $workflow->expects(self::any())
            ->method('getName')
            ->willReturn('workflow_name');

        return $workflow;
    }

    private function getWorkflowItem(array $data): WorkflowItem
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects(self::any())
            ->method('getData')
            ->willReturn(new WorkflowData($data));

        return $workflowItem;
    }

    public function testExecuteNoActiveWorkflow(): void
    {
        $shoppingList = $this->createMock(ShoppingList::class);
        $sourceCriteria = ['shoppingList' => $shoppingList];
        $checkoutData = ['key' => 'value'];

        $this->workflowManager->expects(self::once())
            ->method('getAvailableWorkflowByRecordGroup')
            ->with(Checkout::class, 'b2b_checkout_flow')
            ->willReturn(null);

        $this->actionExecutor->expects(self::never())
            ->method('executeAction');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Active checkout workflow was not found');

        $this->findOrCreateCheckout->execute($sourceCriteria, $checkoutData, false, true, null);
    }

    public function testExecuteWithNotExistingCheckout(): void
    {
        $shoppingList = $this->createMock(ShoppingList::class);
        $sourceCriteria = ['shoppingList' => $shoppingList];
        $checkoutData = ['key' => 'value'];
        $currentCurrency = 'USD';

        $workflow = $this->getWorkflow();
        $user = null;
        $website = $this->createMock(Website::class);
        $workflowItem = $this->createMock(WorkflowItem::class);
        $checkout = new Checkout();

        $this->workflowManager->expects(self::once())
            ->method('getAvailableWorkflowByRecordGroup')
            ->with(Checkout::class, 'b2b_checkout_flow')
            ->willReturn($workflow);

        $this->actionExecutor->expects(self::exactly(2))
            ->method('executeAction')
            ->willReturnMap([
                ['get_active_user_or_null', ['attribute' => null], ['attribute' => $user]],
                ['flush_entity']
            ]);

        $this->checkoutBySourceCriteriaManipulator->expects(self::once())
            ->method('findCheckout')
            ->with($sourceCriteria, $user, $currentCurrency, 'workflow_name')
            ->willReturn($checkout);

        $this->userCurrencyManager->expects(self::once())
            ->method('getUserCurrency')
            ->willReturn($currentCurrency);

        $this->websiteManager->expects(self::once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $this->checkoutBySourceCriteriaManipulator->expects(self::once())
            ->method('createCheckout')
            ->with($website, $sourceCriteria, $user, $currentCurrency, $checkoutData)
            ->willReturn($checkout);

        $this->workflowManager->expects(self::once())
            ->method('startWorkflow')
            ->with($workflow, self::isInstanceOf(Checkout::class), null)
            ->willReturn($workflowItem);

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($this->createMock(AnonymousCustomerUserToken::class));

        $result = $this->findOrCreateCheckout->execute($sourceCriteria, $checkoutData, false, false, null);

        self::assertArrayHasKey('checkout', $result);
        self::assertInstanceOf(Checkout::class, $result['checkout']);
        /** @var Checkout $resultCheckout */
        $resultCheckout = $result['checkout'];
        self::assertSame($checkout, $resultCheckout);

        self::assertArrayHasKey('workflowItem', $result);
        self::assertInstanceOf(WorkflowItem::class, $result['workflowItem']);

        self::assertArrayHasKey('updateData', $result);
        self::assertTrue($result['updateData']);
    }

    public function testExecuteWithForceStartCheckout(): void
    {
        $shoppingList = $this->createMock(ShoppingList::class);
        $sourceCriteria = ['shoppingList' => $shoppingList];
        $checkoutData = ['key' => 'value'];
        $currentCurrency = 'USD';

        $workflow = $this->getWorkflow();
        $user = $this->createMock(CustomerUser::class);
        $website = $this->createMock(Website::class);
        $workflowItem = $this->createMock(WorkflowItem::class);
        $checkout = new Checkout();

        $this->workflowManager->expects(self::once())
            ->method('getAvailableWorkflowByRecordGroup')
            ->with(Checkout::class, 'b2b_checkout_flow')
            ->willReturn($workflow);

        $this->actionExecutor->expects(self::exactly(2))
            ->method('executeAction')
            ->willReturnMap([
                ['get_active_user_or_null', ['attribute' => null], ['attribute' => $user]],
                ['flush_entity']
            ]);

        $this->userCurrencyManager->expects(self::once())
            ->method('getUserCurrency')
            ->willReturn($currentCurrency);

        $this->websiteManager->expects(self::once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $this->checkoutBySourceCriteriaManipulator->expects(self::once())
            ->method('createCheckout')
            ->with($website, $sourceCriteria, $user, $currentCurrency, $checkoutData)
            ->willReturn($checkout);

        $this->workflowManager->expects(self::once())
            ->method('startWorkflow')
            ->with($workflow, self::isInstanceOf(Checkout::class), null)
            ->willReturn($workflowItem);

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($this->createMock(OrganizationToken::class));

        $result = $this->findOrCreateCheckout->execute($sourceCriteria, $checkoutData, false, true, null);

        self::assertArrayHasKey('checkout', $result);
        self::assertInstanceOf(Checkout::class, $result['checkout']);
        /** @var Checkout $resultCheckout */
        $resultCheckout = $result['checkout'];
        self::assertSame($checkout, $resultCheckout);

        self::assertArrayHasKey('workflowItem', $result);
        self::assertInstanceOf(WorkflowItem::class, $result['workflowItem']);

        self::assertArrayHasKey('updateData', $result);
        self::assertTrue($result['updateData']);
    }

    public function testExecuteWithExistingCheckout(): void
    {
        $shoppingList = $this->createMock(ShoppingList::class);
        $sourceCriteria = ['shoppingList' => $shoppingList];
        $checkoutData = ['key' => 'value'];
        $currentCurrency = 'USD';

        $workflow = $this->getWorkflow();
        $user = $this->createMock(CustomerUser::class);
        $website = $this->createMock(Website::class);
        $checkout = $this->getCheckout(1);

        $stateToken = 'statetoken';
        $workflowItem = $this->getWorkflowItem(['state_token' => $stateToken]);
        $checkoutSource = $this->getCheckoutSource($shoppingList);

        $this->workflowManager->expects(self::once())
            ->method('getAvailableWorkflowByRecordGroup')
            ->with(Checkout::class, 'b2b_checkout_flow')
            ->willReturn($workflow);

        $this->actionExecutor->expects(self::exactly(4))
            ->method('executeAction')
            ->willReturnMap([
                ['get_active_user_or_null', ['attribute' => null], ['attribute' => $user]],
                [
                    'create_object',
                    ['class' => CheckoutSource::class, 'data' => $sourceCriteria, 'attribute' => null],
                    ['attribute' => $checkoutSource]
                ],
                [
                    'delete_checkout_state',
                    ['entity' => $checkout, 'token' => $stateToken]
                ],
                ['flush_entity']
            ]);

        $this->userCurrencyManager->expects(self::once())
            ->method('getUserCurrency')
            ->willReturn($currentCurrency);

        $this->checkoutBySourceCriteriaManipulator->expects(self::once())
            ->method('findCheckout')
            ->with($sourceCriteria, $user, $currentCurrency, 'workflow_name')
            ->willReturn($checkout);

        $this->websiteManager->expects(self::once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $this->checkoutBySourceCriteriaManipulator->expects(self::once())
            ->method('actualizeCheckout')
            ->with($checkout, $website, $sourceCriteria, $currentCurrency, $checkoutData, false);

        $checkoutLineItems = new ArrayCollection([$this->createMock(CheckoutLineItem::class)]);
        $this->checkoutLineItemsFactory->expects(self::once())
            ->method('create')
            ->with($shoppingList)
            ->willReturn($checkoutLineItems);

        $this->checkoutCompareHelper->expects(self::once())
            ->method('resetCheckoutIfSourceLineItemsChanged')
            ->with($checkout, self::isInstanceOf(Checkout::class))
            ->willReturn($checkout);

        $this->workflowManager->expects(self::once())
            ->method('getWorkflowItem')
            ->with($checkout, 'workflow_name')
            ->willReturn($workflowItem);

        $result = $this->findOrCreateCheckout->execute($sourceCriteria, $checkoutData, false, false, null);

        self::assertArrayHasKey('checkout', $result);
        self::assertInstanceOf(Checkout::class, $result['checkout']);
        self::assertArrayHasKey('workflowItem', $result);
        self::assertInstanceOf(WorkflowItem::class, $result['workflowItem']);
        self::assertArrayHasKey('updateData', $result);
        self::assertFalse($result['updateData']);
    }

    public function testExecuteWithExistingCheckoutAndAnonymousUser(): void
    {
        $shoppingList = $this->createMock(ShoppingList::class);
        $sourceCriteria = ['shoppingList' => $shoppingList];
        $checkoutData = ['key' => 'value'];
        $currentCurrency = 'USD';

        $workflow = $this->getWorkflow();
        $user = null;
        $website = $this->createMock(Website::class);
        $checkout = $this->getCheckout(1);

        $stateToken = 'statetoken';
        $workflowItem = $this->getWorkflowItem(['state_token' => $stateToken]);
        $checkoutSource = $this->getCheckoutSource($shoppingList);

        $this->workflowManager->expects(self::once())
            ->method('getAvailableWorkflowByRecordGroup')
            ->with(Checkout::class, 'b2b_checkout_flow')
            ->willReturn($workflow);

        $this->actionExecutor->expects(self::exactly(4))
            ->method('executeAction')
            ->willReturnMap([
                ['get_active_user_or_null', ['attribute' => null], ['attribute' => $user]],
                [
                    'create_object',
                    ['class' => CheckoutSource::class, 'data' => $sourceCriteria, 'attribute' => null],
                    ['attribute' => $checkoutSource]
                ],
                [
                    'delete_checkout_state',
                    ['entity' => $checkout, 'token' => $stateToken]
                ],
                ['flush_entity']
            ]);

        $this->userCurrencyManager->expects(self::once())
            ->method('getUserCurrency')
            ->willReturn($currentCurrency);

        $this->checkoutBySourceCriteriaManipulator->expects(self::once())
            ->method('findCheckout')
            ->with($sourceCriteria, $user, $currentCurrency, 'workflow_name')
            ->willReturn($checkout);

        $this->websiteManager->expects(self::once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $this->checkoutBySourceCriteriaManipulator->expects(self::once())
            ->method('actualizeCheckout')
            ->with($checkout, $website, $sourceCriteria, $currentCurrency, $checkoutData, false);

        $checkoutLineItems = new ArrayCollection([$this->createMock(CheckoutLineItem::class)]);
        $this->checkoutLineItemsFactory->expects(self::once())
            ->method('create')
            ->with($shoppingList)
            ->willReturn($checkoutLineItems);

        $this->checkoutCompareHelper->expects(self::once())
            ->method('resetCheckoutIfSourceLineItemsChanged')
            ->with($checkout, self::isInstanceOf(Checkout::class))
            ->willReturn($checkout);

        $this->workflowManager->expects(self::once())
            ->method('getWorkflowItem')
            ->with($checkout, 'workflow_name')
            ->willReturn($workflowItem);

        $result = $this->findOrCreateCheckout->execute($sourceCriteria, $checkoutData, false, false, null);

        self::assertArrayHasKey('checkout', $result);
        self::assertInstanceOf(Checkout::class, $result['checkout']);
        self::assertArrayHasKey('workflowItem', $result);
        self::assertInstanceOf(WorkflowItem::class, $result['workflowItem']);
        self::assertArrayHasKey('updateData', $result);
        self::assertFalse($result['updateData']);
    }
}
