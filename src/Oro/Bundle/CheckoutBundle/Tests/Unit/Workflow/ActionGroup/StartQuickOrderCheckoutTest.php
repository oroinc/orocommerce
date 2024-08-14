<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\ActionGroup;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutRepository;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\StartQuickOrderCheckout;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\StartShoppingListCheckoutInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StartQuickOrderCheckoutTest extends TestCase
{
    private ActionExecutor|MockObject $actionExecutor;
    private UserCurrencyManager|MockObject $userCurrencyManager;
    private CheckoutRepository|MockObject $checkoutRepository;
    private ManagerRegistry|MockObject $registry;
    private StartShoppingListCheckoutInterface|MockObject $startShoppingListCheckout;
    private WorkflowManager|MockObject $workflowManager;

    private StartQuickOrderCheckout $startQuickOrderCheckout;

    protected function setUp(): void
    {
        $this->actionExecutor = $this->createMock(ActionExecutor::class);
        $this->userCurrencyManager = $this->createMock(UserCurrencyManager::class);
        $this->checkoutRepository = $this->createMock(CheckoutRepository::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->startShoppingListCheckout = $this->createMock(StartShoppingListCheckoutInterface::class);
        $this->workflowManager = $this->createMock(WorkflowManager::class);

        $this->startQuickOrderCheckout = new StartQuickOrderCheckout(
            $this->actionExecutor,
            $this->userCurrencyManager,
            $this->checkoutRepository,
            $this->registry,
            $this->startShoppingListCheckout,
            $this->workflowManager
        );
    }

    public function testExecuteWithoutTransitionName(): void
    {
        $shoppingList = new ShoppingList();
        $currentUser = $this->createMock(CustomerUser::class);
        $checkout = new Checkout();
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())
            ->method('getWorkflowName')
            ->willReturn('workflow_name');

        $this->actionExecutor->expects($this->once())
            ->method('executeAction')
            ->with(
                'get_active_user_or_null',
                ['attribute' => null]
            )
            ->willReturn(['attribute' => $currentUser]);

        $this->startShoppingListCheckout->expects($this->once())
            ->method('execute')
            ->with($shoppingList, false, true, true, true, true, false)
            ->willReturn(['checkout' => $checkout, 'workflowItem' => $workflowItem]);

        $result = $this->startQuickOrderCheckout->execute($shoppingList);

        $this->assertEquals(['checkout' => $checkout, 'workflowItem' => $workflowItem], $result);
    }

    public function testExecuteWithoutTransitionNameForGuest(): void
    {
        $shoppingList = new ShoppingList();
        $currentUser = $this->createMock(CustomerUser::class);
        $currentUser->expects($this->any())
            ->method('isGuest')
            ->willReturn(true);
        $checkout = new Checkout();
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())
            ->method('getWorkflowName')
            ->willReturn('workflow_name');

        $workflow = $this->createMock(Workflow::class);
        $workflow->method('getName')->willReturn('b2b_checkout_flow');
        $this->workflowManager->expects($this->once())
            ->method('getAvailableWorkflowByRecordGroup')
            ->with(Checkout::class, 'b2b_checkout_flow')
            ->willReturn($workflow);
        $this->userCurrencyManager->expects($this->once())
            ->method('getUserCurrency')
            ->willReturn('USD');

        $this->checkoutRepository->expects($this->once())
            ->method('findCheckoutByCustomerUserAndSourceCriteriaWithCurrency')
            ->with($currentUser, ['shoppingList' => $shoppingList], 'b2b_checkout_flow', 'USD')
            ->willReturn($checkout);

        $em = $this->createMock(ObjectManager::class);
        $em->expects($this->once())
            ->method('remove')
            ->with($checkout);
        $em->expects($this->once())
            ->method('flush')
            ->with($checkout);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(Checkout::class)
            ->willReturn($em);

        $this->actionExecutor->expects($this->once())
            ->method('executeAction')
            ->with(
                'get_active_user_or_null',
                ['attribute' => null]
            )
            ->willReturn(['attribute' => $currentUser]);

        $this->startShoppingListCheckout->expects($this->once())
            ->method('execute')
            ->with($shoppingList, false, true, true, true, true, false)
            ->willReturn(['checkout' => $checkout, 'workflowItem' => $workflowItem]);

        $result = $this->startQuickOrderCheckout->execute($shoppingList);

        $this->assertEquals(['checkout' => $checkout, 'workflowItem' => $workflowItem], $result);
    }

    public function testExecuteTransitionName(): void
    {
        $shoppingList = new ShoppingList();
        $currentUser = $this->createMock(CustomerUser::class);
        $checkout = new Checkout();
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())
            ->method('getWorkflowName')
            ->willReturn('workflow_name');

        $this->actionExecutor->expects($this->exactly(2))
            ->method('executeAction')
            ->willReturnMap([
                ['get_active_user_or_null', ['attribute' => null], ['attribute' => $currentUser]],
                [
                    'transit_workflow',
                    [
                        'entity' => $checkout,
                        'workflow' => 'workflow_name',
                        'transition' => 'test_transition'
                    ],
                    null
                ]
            ]);

        $this->startShoppingListCheckout->expects($this->once())
            ->method('execute')
            ->with($shoppingList, false, true, true, true, true, false)
            ->willReturn(['checkout' => $checkout, 'workflowItem' => $workflowItem]);

        $result = $this->startQuickOrderCheckout->execute($shoppingList, 'test_transition');

        $this->assertEquals(['checkout' => $checkout, 'workflowItem' => $workflowItem], $result);
    }
}
