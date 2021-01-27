<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ShoppingListBundle\Validator\Constraints\LineItemCollection;
use Oro\Bundle\ShoppingListBundle\Validator\Constraints\LineItemCollectionValidator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilder;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class LineItemCollectionValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $eventDispatcher;

    /**
     * @var LineItemCollectionValidator
     */
    protected $lineItemCollectionValidator;

    /** @var
     * Constraint|\PHPUnit\Framework\MockObject\MockObject $constraint
     */
    protected $constraint;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->lineItemCollectionValidator = new LineItemCollectionValidator($this->eventDispatcher);
        $this->constraint = $this->getMockBuilder(LineItemCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testValidateIgnoredIfNoListeners()
    {
        $this->eventDispatcher->expects($this->once())
            ->method('hasListeners')
            ->willReturn(false);
        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');
        $this->lineItemCollectionValidator->validate([], $this->constraint);
    }

    public function testValidateBuildViolation()
    {
        $this->eventDispatcher->expects($this->once())
            ->method('hasListeners')
            ->willReturn(true);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(
                function ($event, $eventName) {
                    $event->addErrorByUnit('testSku', 'item', 'testMessage');
                }
            );
        $executionContext = $this->createMock(ExecutionContextInterface::class);
        $this->lineItemCollectionValidator->initialize($executionContext);
        $violationBuilder = $this->getMockBuilder(ConstraintViolationBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $executionContext->expects($this->once())
            ->method('buildViolation')
            ->willReturn($violationBuilder);
        $violationBuilder->expects($this->once())
            ->method('atPath')
            ->willReturn($this->createMock(ConstraintViolationBuilderInterface::class));
        $this->lineItemCollectionValidator->validate([], $this->constraint);
    }
}
