<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ShoppingListBundle\Event\LineItemValidateEvent;
use Oro\Bundle\ShoppingListBundle\Validator\Constraints\LineItemCollection;
use Oro\Bundle\ShoppingListBundle\Validator\Constraints\LineItemCollectionValidator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class LineItemCollectionValidatorTest extends ConstraintValidatorTestCase
{
    private EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject $eventDispatcher;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        parent::setUp();
    }

    protected function createValidator(): LineItemCollectionValidator
    {
        return new LineItemCollectionValidator($this->eventDispatcher);
    }

    public function testValidateIgnoredIfNoListeners(): void
    {
        $this->eventDispatcher->expects($this->once())
            ->method('hasListeners')
            ->willReturn(false);
        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');

        $constraint = $this->createMock(LineItemCollection::class);
        $this->validator->validate([], $constraint);

        $this->assertNoViolation();
    }

    public function testValidateBuildViolation(): void
    {
        $this->eventDispatcher->expects($this->once())
            ->method('hasListeners')
            ->willReturn(true);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(function (LineItemValidateEvent $event) {
                $event->addErrorByUnit('testSku', 'item', 'testMessage');

                return $event;
            });

        $constraint = $this->createMock(LineItemCollection::class);
        $this->validator->validate([], $constraint);

        $this->buildViolation('testMessage')
            ->atPath('property.path.product.testSku.item')
            ->assertRaised();
    }
}
