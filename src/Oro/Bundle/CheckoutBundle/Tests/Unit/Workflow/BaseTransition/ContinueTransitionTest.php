<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\BaseTransition;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\OrderLineItemsNotEmptyInterface;
use Oro\Bundle\CheckoutBundle\Workflow\BaseTransition\ContinueTransition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ContinueTransitionTest extends TestCase
{
    private ActionExecutor|MockObject $actionExecutor;
    private OrderLineItemsNotEmptyInterface|MockObject $orderLineItemsNotEmpty;
    private ContinueTransition $transition;

    #[\Override]
    protected function setUp(): void
    {
        $this->actionExecutor = $this->createMock(ActionExecutor::class);
        $this->orderLineItemsNotEmpty = $this->createMock(OrderLineItemsNotEmptyInterface::class);

        $this->transition = new ContinueTransition(
            $this->actionExecutor,
            $this->orderLineItemsNotEmpty
        );
    }

    public function testIsPreConditionNotAllowedWhenCheckoutIsCompleted(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $checkout = $this->createMock(Checkout::class);

        $workflowItem->expects($this->once())
            ->method('getEntity')
            ->willReturn($checkout);

        $checkout->expects($this->once())
            ->method('isCompleted')
            ->willReturn(true);

        $result = $this->transition->isPreConditionAllowed($workflowItem);

        $this->assertFalse($result, 'Pre-condition should not be allowed if checkout is completed.');
    }

    public function testIsPreConditionNotAllowedWhenOrderLineItemsForRfpEmpty(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $checkout = $this->createMock(Checkout::class);

        $workflowItem->expects($this->once())
            ->method('getEntity')
            ->willReturn($checkout);

        $checkout->expects($this->once())
            ->method('isCompleted')
            ->willReturn(false);

        $this->orderLineItemsNotEmpty->expects($this->once())
            ->method('execute')
            ->with($checkout)
            ->willReturn([
                'orderLineItemsNotEmptyForRfp' => false,
                'orderLineItemsNotEmpty' => false,
            ]);

        $errors = new ArrayCollection();

        $result = $this->transition->isPreConditionAllowed($workflowItem, $errors);

        $this->assertFalse($result, 'Pre-condition should not be allowed if order line items are empty.');
        $this->assertCount(1, $errors, 'Errors collection should contain the appropriate error message.');
        $this->assertEqualsCanonicalizing(
            [['message' => 'oro.checkout.workflow.condition.order_line_items_not_empty.not_allow_rfp.message']],
            $errors->toArray()
        );
    }

    public function testIsPreConditionNotAllowedWhenOrderLineItemsEmpty(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $checkout = $this->createMock(Checkout::class);

        $workflowItem->expects($this->once())
            ->method('getEntity')
            ->willReturn($checkout);

        $checkout->expects($this->once())
            ->method('isCompleted')
            ->willReturn(false);

        $this->orderLineItemsNotEmpty->expects($this->once())
            ->method('execute')
            ->with($checkout)
            ->willReturn([
                'orderLineItemsNotEmptyForRfp' => true,
                'orderLineItemsNotEmpty' => false,
            ]);

        $errors = new ArrayCollection();

        $result = $this->transition->isPreConditionAllowed($workflowItem, $errors);

        $this->assertFalse($result, 'Pre-condition should not be allowed if order line items are empty.');
        $this->assertCount(1, $errors, 'Errors collection should contain the appropriate error message.');
        $this->assertEqualsCanonicalizing(
            [['message' => 'oro.checkout.workflow.condition.order_line_items_not_empty.allow_rfp.message']],
            $errors->toArray()
        );
    }

    public function testIsPreConditionNotAllowedWhenQuoteIsNotAcceptable(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $checkout = $this->createMock(Checkout::class);

        $workflowItem->expects($this->once())
            ->method('getEntity')
            ->willReturn($checkout);

        $checkout->expects($this->once())
            ->method('isCompleted')
            ->willReturn(false);

        $this->orderLineItemsNotEmpty->expects($this->once())
            ->method('execute')
            ->with($checkout)
            ->willReturn([
                'orderLineItemsNotEmptyForRfp' => true,
                'orderLineItemsNotEmpty' => true,
            ]);

        $errors = new ArrayCollection();

        $this->actionExecutor
            ->expects($this->once())
            ->method('evaluateExpression')
            ->with('quote_acceptable', [$checkout->getSourceEntity(), true], $errors)
            ->willReturn(false);

        $result = $this->transition->isPreConditionAllowed($workflowItem, $errors);

        $this->assertFalse($result, 'Pre-condition should not be allowed if the quote is not acceptable.');
    }

    public function testIsPreConditionAllowedWhenAllConditionsAreMet(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $checkout = $this->createMock(Checkout::class);

        $workflowItem->expects($this->once())
            ->method('getEntity')
            ->willReturn($checkout);

        $checkout->expects($this->once())
            ->method('isCompleted')
            ->willReturn(false);

        $this->orderLineItemsNotEmpty
            ->expects($this->once())
            ->method('execute')
            ->with($checkout)
            ->willReturn([
                'orderLineItemsNotEmptyForRfp' => true,
                'orderLineItemsNotEmpty' => true,
            ]);

        $errors = new ArrayCollection();

        $this->actionExecutor->expects($this->once())
            ->method('evaluateExpression')
            ->with('quote_acceptable', [$checkout->getSourceEntity(), true], $errors)
            ->willReturn(true);

        $result = $this->transition->isPreConditionAllowed($workflowItem, $errors);

        $this->assertTrue($result, 'Pre-condition should be allowed when all conditions are met.');
    }
}
