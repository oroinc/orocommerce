<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\InventoryBundle\EventListener\CreateOrderCheckUpcomingListener;
use Oro\Bundle\InventoryBundle\Validator\Constraints\CheckoutShipUntil;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Component\Action\Event\ExtendableConditionEvent;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CreateOrderCheckUpcomingListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ValidatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $validator;

    /** @var CreateOrderCheckUpcomingListener */
    private $listener;

    protected function setUp(): void
    {
        $this->validator = $this->createMock(ValidatorInterface::class);

        $this->listener = new CreateOrderCheckUpcomingListener($this->validator);
    }

    public function testOnBeforeOrderCreate(): void
    {
        $checkout = new Checkout();
        $context = new WorkflowItem();
        $context->setEntity($checkout);

        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $violations->expects(self::once())
            ->method('count')
            ->willReturn(0);

        $this->validator->expects(self::once())
            ->method('validate')
            ->with($checkout, new CheckoutShipUntil())
            ->willReturn($violations);

        $event = new ExtendableConditionEvent($context);
        $this->listener->onBeforeOrderCreate($event);

        self::assertCount(0, $event->getErrors());
    }

    public function testOnBeforeOrderCreateError(): void
    {
        $checkout = new Checkout();
        $context = new WorkflowItem();
        $context->setEntity($checkout);

        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $violations->expects(self::once())
            ->method('count')
            ->willReturn(1);

        $this->validator->expects(self::once())
            ->method('validate')
            ->with($checkout, new CheckoutShipUntil())
            ->willReturn($violations);

        $event = new ExtendableConditionEvent($context);
        $this->listener->onBeforeOrderCreate($event);

        self::assertEquals(
            [
                ['message' => '', 'context' => null]
            ],
            $event->getErrors()->toArray()
        );
    }
}
