<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Validator\Constraints;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilder;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

use Oro\Bundle\ShoppingListBundle\Validator\Constraints\LineItemCollectionValidator;

class LineItemCollectionValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventDispatcher;

    /**
     * @var LineItemCollectionValidator
     */
    protected $lineItemCollectionValidator;

    /** @var
     * Constraint|\PHPUnit_Framework_MockObject_MockObject $constraint
     */
    protected $constraint;

    protected function setUp()
    {
        $this->eventDispatcher = $this->getMock(EventDispatcherInterface::class);
        $this->lineItemCollectionValidator = new LineItemCollectionValidator($this->eventDispatcher);
        $this->constraint = $this->getMockBuilder(Constraint::class)
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
                function ($eventName, $event) {
                    $event->addError('testSku', 'testMessage');
                }
            );
        $executionContext = $this->getMock(ExecutionContextInterface::class);
        $this->lineItemCollectionValidator->initialize($executionContext);
        $violationBuilder = $this->getMockBuilder(ConstraintViolationBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $executionContext->expects($this->once())
            ->method('buildViolation')
            ->willReturn($violationBuilder);
        $violationBuilder->expects($this->once())
            ->method('atPath')
            ->willReturn($this->getMock(ConstraintViolationBuilderInterface::class));
        $this->lineItemCollectionValidator->validate([], $this->constraint);
    }
}
