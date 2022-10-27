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
    /**
     * @var ValidatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $validator;

    /**
     * @var CreateOrderCheckUpcomingListener
     */
    protected $listener;

    /**
     * @var ConstraintViolationListInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $violations;

    protected function setUp(): void
    {
        $this->violations = $this->createMock(ConstraintViolationListInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->listener = new CreateOrderCheckUpcomingListener($this->validator);
    }

    public function testOnBeforeOrderCreate()
    {
        $checkout = new Checkout();
        $context = new WorkflowItem();
        $context->setEntity($checkout);

        $this->validator->expects($this->once())
            ->method('validate')
            ->with($checkout, new CheckoutShipUntil())
            ->willReturn($this->violations);

        $this->violations->expects($this->once())->method('count')->willReturn(0);

        $event = new ExtendableConditionEvent($context);
        $this->listener->onBeforeOrderCreate($event);
        $this->assertEmpty($event->getErrors());
    }

    public function testOnBeforeOrderCreateError()
    {
        $checkout = new Checkout();
        $context = new WorkflowItem();
        $context->setEntity($checkout);

        $this->validator->expects($this->once())
            ->method('validate')
            ->with($checkout, new CheckoutShipUntil())
            ->willReturn($this->violations);

        $this->violations->expects($this->once())->method('count')->willReturn(1);

        $event = new ExtendableConditionEvent($context);
        $this->listener->onBeforeOrderCreate($event);
        $this->assertNotEmpty($event->getErrors());
    }
}
