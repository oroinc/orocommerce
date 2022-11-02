<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Event;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Symfony\Component\Form\FormInterface;

class OrderEventTest extends \PHPUnit\Framework\TestCase
{
    public function testEvent()
    {
        $form = $this->createMock(FormInterface::class);
        $order = new Order();
        $event = new OrderEvent($form, $order, ['data']);
        $this->assertIsArray($event->getSubmittedData());
        $this->assertEquals(['data'], $event->getSubmittedData());
        $this->assertSame($order, $event->getOrder());
        $this->assertSame($form, $event->getForm());
        $this->assertInstanceOf(\ArrayObject::class, $event->getData());
    }

    public function testEventNullSubmitted()
    {
        $event = new OrderEvent($this->createMock(FormInterface::class), new Order());
        self::assertNull($event->getSubmittedData());
    }
}
