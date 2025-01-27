<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\BaseTransition;

use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Workflow\BaseTransition\PlaceOrder;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PlaceOrderTest extends TestCase
{
    private ActionExecutor|MockObject $actionExecutor;
    private TransitionServiceInterface|MockObject $baseContinueTransition;
    private ValidatorInterface|MockObject $validator;

    private PlaceOrder $placeOrder;

    #[\Override]
    protected function setUp(): void
    {
        $this->actionExecutor = $this->createMock(ActionExecutor::class);
        $this->baseContinueTransition = $this->createMock(TransitionServiceInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);

        $this->placeOrder = $this->getMockBuilder(PlaceOrder::class)
            ->setConstructorArgs([$this->actionExecutor, $this->baseContinueTransition])
            ->getMockForAbstractClass();
        $this->placeOrder->setValidator($this->validator);
    }

    public function testIsPreConditionAllowedReturnsFalseIfNoWorkflowItemId(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);

        $workflowItem->expects(self::once())
            ->method('getId')
            ->willReturn(null);

        self::assertFalse($this->placeOrder->isPreConditionAllowed($workflowItem));
    }

    public function testIsPreConditionAllowedReturnsFalseIfBaseContinueTransitionDisallows(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects(self::once())
            ->method('getId')
            ->willReturn(1);

        $this->baseContinueTransition->expects(self::once())
            ->method('isPreConditionAllowed')
            ->with($workflowItem)
            ->willReturn(false);

        self::assertFalse($this->placeOrder->isPreConditionAllowed($workflowItem));
    }

    public function testIsPreConditionAllowedReturnsFalseIfValidationNotPassed(): void
    {
        $checkout = new Checkout();
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects(self::once())
            ->method('getId')
            ->willReturn(1);
        $workflowItem->expects(self::once())
            ->method('getEntity')
            ->willReturn($checkout);

        $this->baseContinueTransition->expects(self::once())
            ->method('isPreConditionAllowed')
            ->with($workflowItem)
            ->willReturn(true);

        $violations = new ConstraintViolationList([
            $this->createMock(ConstraintViolationInterface::class)
        ]);
        $this->validator->expects(self::once())
            ->method('validate')
            ->with($checkout, null, 'checkout_order_create_pre_checks')
            ->willReturn($violations);

        self::assertFalse($this->placeOrder->isPreConditionAllowed($workflowItem));
    }

    public function testIsPreConditionAllowedReturnsTrue(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects(self::once())
            ->method('getId')
            ->willReturn(1);
        $checkout = new Checkout();
        $workflowItem->expects(self::once())
            ->method('getEntity')
            ->willReturn($checkout);

        $this->baseContinueTransition->expects(self::once())
            ->method('isPreConditionAllowed')
            ->with($workflowItem)
            ->willReturn(true);

        $violations = new ConstraintViolationList([]);
        $this->validator->expects(self::once())
            ->method('validate')
            ->with($checkout, null, 'checkout_order_create_pre_checks')
            ->willReturn($violations);

        self::assertTrue($this->placeOrder->isPreConditionAllowed($workflowItem));
    }

    /**
     * @dataProvider trueFalseDataProvider
     */
    public function testIsConditionAllowed(bool $isAllowed): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $checkout = $this->createMock(Checkout::class);
        $workflowItem->expects(self::once())
            ->method('getEntity')
            ->willReturn($checkout);

        $violationsArray = [];
        if (!$isAllowed) {
            $violationsArray[] = $this->createMock(ConstraintViolationInterface::class);
        }
        $violations = new ConstraintViolationList($violationsArray);
        $this->validator->expects(self::once())
            ->method('validate')
            ->with($checkout, null, 'checkout_order_create_checks')
            ->willReturn($violations);

        self::assertSame($isAllowed, $this->placeOrder->isConditionAllowed($workflowItem));
    }

    public static function trueFalseDataProvider(): array
    {
        return [
            [true],
            [false]
        ];
    }
}
