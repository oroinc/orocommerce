<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\BaseTransition;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Workflow\BaseTransition\ContinueTransition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ContinueTransitionTest extends TestCase
{
    private ValidatorInterface|MockObject $validator;
    private ContinueTransition $transition;

    #[\Override]
    protected function setUp(): void
    {
        $this->validator = $this->createMock(ValidatorInterface::class);

        $this->transition = new ContinueTransition();
        $this->transition->setValidator($this->validator);
    }

    public function testIsPreConditionNotAllowedWhenCheckoutIsCompleted(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $checkout = $this->createMock(Checkout::class);

        $workflowItem->expects(self::once())
            ->method('getEntity')
            ->willReturn($checkout);

        $checkout->expects(self::once())
            ->method('isCompleted')
            ->willReturn(true);

        $result = $this->transition->isPreConditionAllowed($workflowItem);

        self::assertFalse($result, 'Pre-condition should not be allowed if checkout is completed.');
    }

    public function testIsPreConditionNotAllowedWhenValidationFailed(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $checkout = $this->createMock(Checkout::class);

        $workflowItem->expects(self::once())
            ->method('getEntity')
            ->willReturn($checkout);

        $checkout->expects(self::once())
            ->method('isCompleted')
            ->willReturn(false);

        $violation = $this->createMock(ConstraintViolationInterface::class);
        $violation->expects(self::once())
            ->method('getMessageTemplate')
            ->willReturn('error1');
        $violations = new ConstraintViolationList([$violation]);
        $this->validator->expects(self::once())
            ->method('validate')
            ->with($checkout, null, 'checkout_pre_checks')
            ->willReturn($violations);

        $errors = new ArrayCollection();
        $result = $this->transition->isPreConditionAllowed($workflowItem, $errors);

        self::assertFalse($result, 'Pre-condition should not be allowed if order line items are empty.');
        self::assertCount(1, $errors, 'Errors collection should contain the appropriate error message.');
        self::assertEqualsCanonicalizing([['message' => 'error1', 'parameters' => []]], $errors->toArray());
    }

    public function testIsPreConditionAllowedWhenAllConditionsAreMet(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $checkout = $this->createMock(Checkout::class);

        $workflowItem->expects(self::once())
            ->method('getEntity')
            ->willReturn($checkout);

        $checkout->expects(self::once())
            ->method('isCompleted')
            ->willReturn(false);

        $violations = new ConstraintViolationList([]);
        $this->validator->expects(self::once())
            ->method('validate')
            ->with($checkout, null, 'checkout_pre_checks')
            ->willReturn($violations);

        $result = $this->transition->isPreConditionAllowed($workflowItem, new ArrayCollection());

        self::assertTrue($result, 'Pre-condition should be allowed when all conditions are met.');
    }
}
