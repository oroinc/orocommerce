<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\ActionGroup;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutRepository;
use Oro\Bundle\CheckoutBundle\Factory\CheckoutLineItemsFactory;
use Oro\Bundle\CheckoutBundle\Helper\CheckoutCompareHelper;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\ActualizeCheckoutInterface;
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
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class FindOrCreateCheckoutTest extends TestCase
{
    use EntityTrait;

    private ActionExecutor|MockObject $actionExecutor;
    private WorkflowManager|MockObject $workflowManager;
    private UserCurrencyManager|MockObject $userCurrencyManager;
    private CheckoutRepository|MockObject $checkoutRepository;
    private WebsiteManager|MockObject $websiteManager;
    private ActualizeCheckoutInterface|MockObject $actualizeCheckout;
    private CheckoutLineItemsFactory|MockObject $checkoutLineItemsFactory;
    private CheckoutCompareHelper|MockObject $checkoutCompareHelper;
    private TokenStorageInterface|MockObject $tokenStorage;
    private FindOrCreateCheckout $findOrCreateCheckout;

    protected function setUp(): void
    {
        $this->actionExecutor = $this->createMock(ActionExecutor::class);
        $this->workflowManager = $this->createMock(WorkflowManager::class);
        $this->userCurrencyManager = $this->createMock(UserCurrencyManager::class);
        $this->checkoutRepository = $this->createMock(CheckoutRepository::class);
        $this->websiteManager = $this->createMock(WebsiteManager::class);
        $this->actualizeCheckout = $this->createMock(ActualizeCheckoutInterface::class);
        $this->checkoutLineItemsFactory = $this->createMock(CheckoutLineItemsFactory::class);
        $this->checkoutCompareHelper = $this->createMock(CheckoutCompareHelper::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);

        $this->findOrCreateCheckout = new FindOrCreateCheckout(
            $this->actionExecutor,
            $this->workflowManager,
            $this->userCurrencyManager,
            $this->checkoutRepository,
            $this->websiteManager,
            $this->actualizeCheckout,
            $this->checkoutLineItemsFactory,
            $this->checkoutCompareHelper,
            $this->tokenStorage
        );
    }

    public function testExecuteNoActiveWorkflow(): void
    {
        $shoppingList = $this->createMock(ShoppingList::class);
        $sourceCriteria = ['shoppingList' => $shoppingList];
        $checkoutData = ['key' => 'value'];

        $this->workflowManager->expects($this->once())
            ->method('getAvailableWorkflowByRecordGroup')
            ->with(Checkout::class, 'b2b_checkout_flow')
            ->willReturn(null);

        $this->actionExecutor->expects($this->never())
            ->method('executeAction');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Active checkout workflow was not found');

        $this->findOrCreateCheckout->execute($sourceCriteria, $checkoutData, false, true, null);
    }

    public function testExecuteWithNotExistingCheckout()
    {
        $shoppingList = $this->createMock(ShoppingList::class);
        $sourceCriteria = ['shoppingList' => $shoppingList];
        $checkoutData = ['key' => 'value'];
        $currentCurrency = 'USD';

        $workflow = $this->prepareWorkflow();
        $user = null;
        $website = $this->createMock(Website::class);
        $checkoutSource = $this->createMock(CheckoutSource::class);
        $workflowItem = $this->createMock(WorkflowItem::class);

        $this->assertGetCurrentWorkflowCall($workflow);

        $this->actionExecutor->expects($this->any())
            ->method('executeAction')
            ->willReturnMap([
                ['get_active_user_or_null', ['attribute' => null], ['attribute' => $user]],
                [
                    'create_entity',
                    [
                        'class' => CheckoutSource::class,
                        'data' => $sourceCriteria,
                        'attribute' => null
                    ],
                    ['attribute' => $checkoutSource]
                ],
                ['flush_entity']
            ]);

        $this->checkoutRepository->expects($this->once())
            ->method('findCheckoutBySourceCriteriaWithCurrency')
            ->with($sourceCriteria, 'workflow_name', $currentCurrency)
            ->willReturn(null);

        $this->userCurrencyManager->expects($this->once())
            ->method('getUserCurrency')
            ->willReturn($currentCurrency);

        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $this->actualizeCheckout->expects($this->once())
            ->method('execute')
            ->with($this->isInstanceOf(Checkout::class), $sourceCriteria, $website, true, $checkoutData);

        $this->workflowManager->expects($this->once())
            ->method('startWorkflow')
            ->with($workflow, $this->isInstanceOf(Checkout::class), null)
            ->willReturn($workflowItem);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($this->createMock(AnonymousCustomerUserToken::class));

        $result = $this->findOrCreateCheckout->execute($sourceCriteria, $checkoutData, false, false, null);

        $this->assertArrayHasKey('checkout', $result);
        $this->assertInstanceOf(Checkout::class, $result['checkout']);
        /** @var Checkout $resultCheckout */
        $resultCheckout = $result['checkout'];
        $this->assertEquals($checkoutSource, $resultCheckout->getSource());
        $this->assertEquals($website, $resultCheckout->getWebsite());
        $this->assertEquals($user, $resultCheckout->getCustomerUser());

        $this->assertArrayHasKey('workflowItem', $result);
        $this->assertInstanceOf(WorkflowItem::class, $result['workflowItem']);

        $this->assertArrayHasKey('updateData', $result);
        $this->assertTrue($result['updateData']);
    }

    public function testExecuteWithForceStartCheckout()
    {
        $shoppingList = $this->createMock(ShoppingList::class);
        $sourceCriteria = ['shoppingList' => $shoppingList];
        $checkoutData = ['key' => 'value'];
        $currentCurrency = 'USD';

        $workflow = $this->prepareWorkflow();
        $user = $this->createMock(CustomerUser::class);
        $website = $this->createMock(Website::class);
        $checkoutSource = $this->createMock(CheckoutSource::class);
        $workflowItem = $this->createMock(WorkflowItem::class);

        $this->assertGetCurrentWorkflowCall($workflow);

        $this->actionExecutor->expects($this->any())
            ->method('executeAction')
            ->willReturnMap([
                ['get_active_user_or_null', ['attribute' => null], ['attribute' => $user]],
                [
                    'create_entity',
                    [
                        'class' => CheckoutSource::class,
                        'data' => $sourceCriteria,
                        'attribute' => null
                    ],
                    ['attribute' => $checkoutSource]
                ],
                ['flush_entity']
            ]);

        $this->userCurrencyManager->expects($this->once())
            ->method('getUserCurrency')
            ->willReturn($currentCurrency);

        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $this->actualizeCheckout->expects($this->once())
            ->method('execute')
            ->with($this->isInstanceOf(Checkout::class), $sourceCriteria, $website, true, $checkoutData);

        $this->workflowManager->expects($this->once())
            ->method('startWorkflow')
            ->with($workflow, $this->isInstanceOf(Checkout::class), null)
            ->willReturn($workflowItem);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($this->createMock(OrganizationToken::class));

        $result = $this->findOrCreateCheckout->execute($sourceCriteria, $checkoutData, false, true, null);

        $this->assertArrayHasKey('checkout', $result);
        $this->assertInstanceOf(Checkout::class, $result['checkout']);
        /** @var Checkout $resultCheckout */
        $resultCheckout = $result['checkout'];
        $this->assertEquals($checkoutSource, $resultCheckout->getSource());
        $this->assertEquals($website, $resultCheckout->getWebsite());
        $this->assertEquals($user, $resultCheckout->getCustomerUser());

        $this->assertArrayHasKey('workflowItem', $result);
        $this->assertInstanceOf(WorkflowItem::class, $result['workflowItem']);

        $this->assertArrayHasKey('updateData', $result);
        $this->assertTrue($result['updateData']);
    }

    public function testExecuteWithExistingCheckout()
    {
        $shoppingList = $this->createMock(ShoppingList::class);
        $sourceCriteria = ['shoppingList' => $shoppingList];
        $checkoutData = ['key' => 'value'];
        $currentCurrency = 'USD';

        $workflow = $this->prepareWorkflow();
        $user = $this->createMock(CustomerUser::class);
        $website = $this->createMock(Website::class);
        $checkout = $this->getEntity(Checkout::class, ['id' => 1]);

        $stateToken = 'statetoken';
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())
            ->method('getData')
            ->willReturn(new WorkflowData(['state_token' => $stateToken]));
        $checkoutSource = $this->createMock(CheckoutSource::class);
        $checkoutSource->expects($this->any())
            ->method('getEntity')
            ->willReturn($shoppingList);

        $this->assertGetCurrentWorkflowCall($workflow);

        $this->actionExecutor->expects($this->any())
            ->method('executeAction')
            ->willReturnMap([
                ['get_active_user_or_null', ['attribute' => null], ['attribute' => $user]],
                [
                    'create_object',
                    [
                        'class' => CheckoutSource::class,
                        'data' => $sourceCriteria,
                        'attribute' => null
                    ],
                    ['attribute' => $checkoutSource]
                ],
                [
                    'delete_checkout_state',
                    [
                        'entity' => $checkout,
                        'token' => $stateToken
                    ]
                ],
                ['flush_entity']
            ]);

        $this->userCurrencyManager->expects($this->once())
            ->method('getUserCurrency')
            ->willReturn($currentCurrency);

        $this->checkoutRepository->expects($this->once())
            ->method('findCheckoutByCustomerUserAndSourceCriteriaWithCurrency')
            ->with($user, $sourceCriteria, 'workflow_name', $currentCurrency)
            ->willReturn($checkout);

        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $this->actualizeCheckout->expects($this->once())
            ->method('execute')
            ->with($checkout, $sourceCriteria, $website, false, $checkoutData);

        $checkoutLineItems = new ArrayCollection([$this->createMock(CheckoutLineItem::class)]);
        $this->checkoutLineItemsFactory->expects($this->once())
            ->method('create')
            ->with($shoppingList)
            ->willReturn($checkoutLineItems);

        $this->checkoutCompareHelper->expects($this->once())
            ->method('resetCheckoutIfSourceLineItemsChanged')
            ->with($checkout, $this->isInstanceOf(Checkout::class))
            ->willReturn($checkout);

        $this->workflowManager->expects($this->once())
            ->method('getWorkflowItem')
            ->with($checkout, 'workflow_name')
            ->willReturn($workflowItem);

        $result = $this->findOrCreateCheckout->execute($sourceCriteria, $checkoutData, false, false, null);

        $this->assertArrayHasKey('checkout', $result);
        $this->assertInstanceOf(Checkout::class, $result['checkout']);
        $this->assertArrayHasKey('workflowItem', $result);
        $this->assertInstanceOf(WorkflowItem::class, $result['workflowItem']);
        $this->assertArrayHasKey('updateData', $result);
        $this->assertFalse($result['updateData']);
    }

    public function testExecuteWithExistingCheckoutAndAnonymousUser()
    {
        $shoppingList = $this->createMock(ShoppingList::class);
        $sourceCriteria = ['shoppingList' => $shoppingList];
        $checkoutData = ['key' => 'value'];
        $currentCurrency = 'USD';

        $workflow = $this->prepareWorkflow();
        $user = null;
        $website = $this->createMock(Website::class);
        $checkout = $this->getEntity(Checkout::class, ['id' => 1]);

        $stateToken = 'statetoken';
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())
            ->method('getData')
            ->willReturn(new WorkflowData(['state_token' => $stateToken]));
        $checkoutSource = $this->createMock(CheckoutSource::class);
        $checkoutSource->expects($this->any())
            ->method('getEntity')
            ->willReturn($shoppingList);

        $this->assertGetCurrentWorkflowCall($workflow);

        $this->actionExecutor->expects($this->any())
            ->method('executeAction')
            ->willReturnMap([
                ['get_active_user_or_null', ['attribute' => null], ['attribute' => $user]],
                [
                    'create_object',
                    [
                        'class' => CheckoutSource::class,
                        'data' => $sourceCriteria,
                        'attribute' => null
                    ],
                    ['attribute' => $checkoutSource]
                ],
                [
                    'delete_checkout_state',
                    [
                        'entity' => $checkout,
                        'token' => $stateToken
                    ]
                ],
                ['flush_entity']
            ]);

        $this->userCurrencyManager->expects($this->once())
            ->method('getUserCurrency')
            ->willReturn($currentCurrency);

        $this->checkoutRepository->expects($this->once())
            ->method('findCheckoutBySourceCriteriaWithCurrency')
            ->with($sourceCriteria, 'workflow_name', $currentCurrency)
            ->willReturn($checkout);

        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $this->actualizeCheckout->expects($this->once())
            ->method('execute')
            ->with($checkout, $sourceCriteria, $website, false, $checkoutData);

        $checkoutLineItems = new ArrayCollection([$this->createMock(CheckoutLineItem::class)]);
        $this->checkoutLineItemsFactory->expects($this->once())
            ->method('create')
            ->with($shoppingList)
            ->willReturn($checkoutLineItems);

        $this->checkoutCompareHelper->expects($this->once())
            ->method('resetCheckoutIfSourceLineItemsChanged')
            ->with($checkout, $this->isInstanceOf(Checkout::class))
            ->willReturn($checkout);

        $this->workflowManager->expects($this->once())
            ->method('getWorkflowItem')
            ->with($checkout, 'workflow_name')
            ->willReturn($workflowItem);

        $result = $this->findOrCreateCheckout->execute($sourceCriteria, $checkoutData, false, false, null);

        $this->assertArrayHasKey('checkout', $result);
        $this->assertInstanceOf(Checkout::class, $result['checkout']);
        $this->assertArrayHasKey('workflowItem', $result);
        $this->assertInstanceOf(WorkflowItem::class, $result['workflowItem']);
        $this->assertArrayHasKey('updateData', $result);
        $this->assertFalse($result['updateData']);
    }

    private function assertGetCurrentWorkflowCall(Workflow $workflow): void
    {
        $this->workflowManager->expects($this->once())
            ->method('getAvailableWorkflowByRecordGroup')
            ->with(Checkout::class, 'b2b_checkout_flow')
            ->willReturn($workflow);
    }

    private function prepareWorkflow(): Workflow|MockObject
    {
        $workflow = $this->createMock(Workflow::class);
        $workflow->expects($this->any())
            ->method('getName')
            ->willReturn('workflow_name');

        return $workflow;
    }
}
