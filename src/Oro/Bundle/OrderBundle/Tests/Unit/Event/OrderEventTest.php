<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Event;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Event\OrderEvent;

class OrderEventTest extends \PHPUnit_Framework_TestCase
{
    public function testEvent()
    {
        $form = $this->createMock('Symfony\Component\Form\FormInterface');
        $order = new Order();
        $event = new OrderEvent($form, $order, ['data']);
        $this->assertInternalType('array', $event->getSubmittedData());
        $this->assertEquals(['data'], $event->getSubmittedData());
        $this->assertSame($order, $event->getOrder());
        $this->assertSame($form, $event->getForm());
        $this->assertInstanceOf('\ArrayObject', $event->getData());
    }

    public function testEventNullSubmitted()
    {
        $event = new OrderEvent($this->createMock('Symfony\Component\Form\FormInterface'), new Order());
        static::assertNull($event->getSubmittedData());
    }
}
