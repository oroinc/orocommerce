<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Event;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Event\OrderEvent;

class OrderEventTest extends \PHPUnit_Framework_TestCase
{
    public function testEvent()
    {
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $order = new Order();
        $event = new OrderEvent($form, $order, ['data']);
        $this->assertInternalType('array', $event->getSubmittedData());
        $this->assertEquals(['data'], $event->getSubmittedData());
        $this->assertSame($order, $event->getOrder());
        $this->assertSame($form, $event->getForm());
        $this->assertInstanceOf('\ArrayObject', $event->getData());
    }
}
