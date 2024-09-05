<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\BaseTransition;

use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutPaymentContextProvider;
use Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\ActionGroup\CheckoutActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\ActionGroup\OrderActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\BaseTransition\PlaceOrder;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceInterface;
use Oro\Bundle\WorkflowBundle\Model\WorkflowResult;
use Oro\Component\Action\Condition\ExtendableCondition;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PlaceOrderTest extends TestCase
{
    private ActionExecutor|MockObject $actionExecutor;
    private CheckoutPaymentContextProvider|MockObject $paymentContextProvider;
    private OrderActionsInterface|MockObject $orderActions;
    private CheckoutActionsInterface|MockObject $checkoutActions;
    private TransitionServiceInterface|MockObject $baseContinueTransition;

    private PlaceOrder $placeOrder;

    protected function setUp(): void
    {
        $this->actionExecutor = $this->createMock(ActionExecutor::class);
        $this->paymentContextProvider = $this->createMock(CheckoutPaymentContextProvider::class);
        $this->orderActions = $this->createMock(OrderActionsInterface::class);
        $this->checkoutActions = $this->createMock(CheckoutActionsInterface::class);
        $this->baseContinueTransition = $this->createMock(TransitionServiceInterface::class);

        $this->placeOrder = $this->getMockBuilder(PlaceOrder::class)
            ->setConstructorArgs([
                $this->actionExecutor,
                $this->paymentContextProvider,
                $this->orderActions,
                $this->checkoutActions,
                $this->baseContinueTransition
            ])
            ->getMockForAbstractClass();
    }

    public function testIsPreConditionAllowedReturnsFalseIfNoWorkflowItemId(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);

        $workflowItem->expects($this->once())
            ->method('getId')
            ->willReturn(null);

        $this->assertFalse($this->placeOrder->isPreConditionAllowed($workflowItem));
    }

    public function testIsPreConditionAllowedReturnsFalseIfBaseContinueTransitionDisallows(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $this->baseContinueTransition->expects($this->once())
            ->method('isPreConditionAllowed')
            ->with($workflowItem)
            ->willReturn(false);

        $this->assertFalse($this->placeOrder->isPreConditionAllowed($workflowItem));
    }

    public function testIsPreConditionAllowedReturnsFalseIfPreOrderCreateNotAllowedInResult(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $this->baseContinueTransition->expects($this->once())
            ->method('isPreConditionAllowed')
            ->with($workflowItem)
            ->willReturn(true);

        $result = new WorkflowResult([
            'extendableConditionPreOrderCreate' => false
        ]);
        $workflowItem->expects($this->once())
            ->method('getResult')
            ->willReturn($result);

        $this->assertFalse($this->placeOrder->isPreConditionAllowed($workflowItem));
    }

    public function testIsPreConditionAllowedReturnsFalseIfPreOrderCreateNotAllowed(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->once())
            ->method('getId')
            ->willReturn(1);
        $checkout = new Checkout();
        $workflowItem->expects($this->any())
            ->method('getEntity')
            ->willReturn($checkout);

        $this->baseContinueTransition->expects($this->once())
            ->method('isPreConditionAllowed')
            ->with($workflowItem)
            ->willReturn(true);

        $result = new WorkflowResult([]);
        $workflowItem->expects($this->once())
            ->method('getResult')
            ->willReturn($result);

        $this->actionExecutor->expects($this->once())
            ->method('evaluateExpression')
            ->with(
                ExtendableCondition::NAME,
                [
                    'events' => ['extendable_condition.pre_order_create'],
                    'eventData' => ['checkout' => $checkout]
                ]
            )
            ->willReturn(false);

        $this->assertFalse($this->placeOrder->isPreConditionAllowed($workflowItem));
        $this->assertFalse($result->offsetGet('extendableConditionPreOrderCreate'));
    }

    public function testIsPreConditionAllowedReturnsTrue(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->once())
            ->method('getId')
            ->willReturn(1);
        $checkout = new Checkout();
        $workflowItem->expects($this->any())
            ->method('getEntity')
            ->willReturn($checkout);

        $this->baseContinueTransition->expects($this->once())
            ->method('isPreConditionAllowed')
            ->with($workflowItem)
            ->willReturn(true);

        $result = new WorkflowResult([]);
        $workflowItem->expects($this->once())
            ->method('getResult')
            ->willReturn($result);

        $this->actionExecutor->expects($this->once())
            ->method('evaluateExpression')
            ->with(
                ExtendableCondition::NAME,
                [
                    'events' => ['extendable_condition.pre_order_create'],
                    'eventData' => ['checkout' => $checkout]
                ]
            )
            ->willReturn(true);

        $this->assertTrue($this->placeOrder->isPreConditionAllowed($workflowItem));
        $this->assertTrue($result->offsetGet('extendableConditionPreOrderCreate'));
    }

    /**
     * @dataProvider trueFalseDataProvider
     */
    public function testIsConditionAllowed(bool $isAllowed): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $checkout = $this->createMock(Checkout::class);
        $workflowItem->expects($this->once())
            ->method('getEntity')
            ->willReturn($checkout);

        $this->actionExecutor->expects($this->once())
            ->method('evaluateExpression')
            ->with(
                ExtendableCondition::NAME,
                [
                    'events' => ['extendable_condition.before_order_create'],
                    'eventData' => ['checkout' => $checkout]
                ],
                null,
                'oro.checkout.workflow.b2b_flow_checkout.transition.place_order.condition.extendable.message'
            )
            ->willReturn($isAllowed);

        $this->assertSame($isAllowed, $this->placeOrder->isConditionAllowed($workflowItem));
    }

    public static function trueFalseDataProvider(): array
    {
        return [
            [true],
            [false]
        ];
    }
}
