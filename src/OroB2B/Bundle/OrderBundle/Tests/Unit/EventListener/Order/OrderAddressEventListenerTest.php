<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\EventListener\Order;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Templating\EngineInterface;

use Oro\Bundle\AddressBundle\Entity\AddressType;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Event\OrderEvent;
use OroB2B\Bundle\OrderBundle\EventListener\Order\OrderAddressEventListener;

class OrderAddressEventListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var OrderAddressEventListener */
    protected $listener;

    /** @var EngineInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $twigEngine;


    protected function setUp()
    {
        $this->twigEngine = $this->getMock('Symfony\Component\Templating\EngineInterface');

        $this->listener = new OrderAddressEventListener($this->twigEngine);
    }

    protected function tearDown()
    {
        unset($this->listener, $this->twigEngine);
    }

    public function testOnOrderEvent()
    {
        $order = new Order();

        /** @var Form|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();

        $form->expects($this->any())
            ->method('getName')
            ->willReturn('name');

        $form->expects($this->any())
            ->method('getData')
            ->willReturn([]);

        $billingAddressField = sprintf('%sAddress', AddressType::TYPE_BILLING);
        $shippingAddressField = sprintf('%sAddress', AddressType::TYPE_SHIPPING);

        $form->expects($this->exactly(2))
            ->method('has')
            ->withConsecutive($this->equalTo($billingAddressField), $this->equalTo($shippingAddressField))
            ->willReturn(true);

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $field1 */
        $field1 = $this->getMock('Symfony\Component\Form\FormInterface');

        $field1View = $this->getMockBuilder('Symfony\Component\Form\FormView')
            ->disableOriginalConstructor()
            ->getMock();

        $field1->expects($this->once())
            ->method('createView')
            ->willReturn($field1View);

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $field2 */
        $field2 = $this->getMock('Symfony\Component\Form\FormInterface');

        $field2View = $this->getMockBuilder('Symfony\Component\Form\FormView')
            ->disableOriginalConstructor()
            ->getMock();

        $field2->expects($this->once())
            ->method('createView')
            ->willReturn($field2View);

        $form->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive([$billingAddressField], [$shippingAddressField])
            ->willReturnOnConsecutiveCalls($field1, $field2);

        $this->twigEngine->expects($this->exactly(2))
            ->method('render')
            ->withConsecutive(
                ['OroB2BOrderBundle:Form:accountAddressSelector.html.twig', ['form' => $field1View]],
                ['OroB2BOrderBundle:Form:accountAddressSelector.html.twig', ['form' => $field2View]]
            )
            ->willReturnOnConsecutiveCalls('view1', 'view2');

        $event = new OrderEvent($form, $order);
        $this->listener->onOrderEvent($event);

        $eventData = $event->getData()->getArrayCopy();

        foreach ([$billingAddressField => 'view1', $shippingAddressField => 'view2'] as $fieldName => $value) {
            $this->assertArrayHasKey($fieldName, $eventData);
            $this->assertEquals($value, $eventData[$fieldName]);
        }
    }
}
