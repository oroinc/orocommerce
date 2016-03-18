<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Form\Type\EventListener;

use OroB2B\Bundle\OrderBundle\Form\Type\EventListener\SubtotalSubscriber;
use OroB2B\Bundle\OrderBundle\Provider\DiscountSubtotalProvider;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;

class SubtotalSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /** @var SubtotalSubscriber */
    protected $subscriber;

    /** @var TotalProcessorProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $totalProvider;

    /** @var LineItemSubtotalProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $lineItemSubtotalProvider;

    /** @var DiscountSubtotalProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $discountSubtotalProvider;

    protected function setUp()
    {
        $this->totalProvider = $this->getMockBuilder(
            'OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->lineItemSubtotalProvider = $this->getMockBuilder(
            'OroB2B\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->discountSubtotalProvider = $this->getMockBuilder(
            'OroB2B\Bundle\OrderBundle\Provider\DiscountSubtotalProvider'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->subscriber = new SubtotalSubscriber(
            $this->totalProvider,
            $this->lineItemSubtotalProvider,
            $this->discountSubtotalProvider
        );
    }

//    public function testProcessUnsupportedRequest()
//    {
//        $this->request->setMethod('GET');
//
//        $this->form->expects($this->never())
//            ->method('submit');
//
//        $this->assertFalse($this->handler->process($this->entity));
//    }
//
//    /**
//     * @dataProvider supportedMethods
//     * @param string $method
//     * @param boolean $isValid
//     * @param boolean $isProcessed
//     */
//    public function testProcessSupportedRequest($method, $isValid, $isProcessed)
//    {
//        $subtotal = new Subtotal();
//        $amount = 42;
//        $subtotal->setType(LineItemSubtotalProvider::TYPE);
//        $subtotal->setAmount($amount);
//
////        $this->totalsProvider->expects($this->any())
////            ->method('getSubtotal')
////            ->willReturn($subtotal);
//
//        $this->form->expects($this->any())
//            ->method('isValid')
//            ->will($this->returnValue($isValid));
//
//        $this->request->setMethod($method);
//
//        $this->form->expects($this->once())
//            ->method('submit')
//            ->with($this->request);
//
//        $this->assertEquals($isProcessed, $this->handler->process($this->entity));
//    }
//
//    /**
//     * @return array
//     */
//    public function supportedMethods()
//    {
//        return [
//            'post valid' => [
//                'method' => 'POST',
//                'isValid' => true,
//                'isProcessed' => true
//            ],
//            'invalid' => [
//                'method' => 'POST',
//                'isValid' => false,
//                'isProcessed' => false
//            ],
//        ];
//    }
//
//    public function testProcessValidData()
//    {
//        $subtotal = new Subtotal();
//        $subtotalAmount = 42;
//        $subtotal->setType(LineItemSubtotalProvider::TYPE);
//        $subtotal->setAmount($subtotalAmount);
//
//        $discountSubtotal = new Subtotal();
//        $discountSubtotalAmount = 42;
//        $discountSubtotal->setType(DiscountSubtotalProvider::TYPE);
//        $discountSubtotal->setAmount($discountSubtotalAmount);
//
//        $discountSubtotal2 = new Subtotal();
//        $discountSubtotalAmount2 = -40;
//        $discountSubtotal2->setType(DiscountSubtotalProvider::TYPE);
//        $discountSubtotal2->setAmount($discountSubtotalAmount2);
//
//        $total = new Subtotal();
//        $totalAmount = 90;
//        $total->setType(TotalProcessorProvider::TYPE);
//        $total->setAmount($totalAmount);
//
//        $this->lineItemSubtotalProvider->expects($this->any())
//            ->method('getSubtotal')
//            ->willReturn($subtotal);
//
//        $this->discountSubtotalProvider->expects($this->any())
//            ->method('getSubtotal')
//            ->willReturn([$discountSubtotal, $discountSubtotal2]);
//
//        $this->totalsProvider->expects($this->any())
//            ->method('getTotal')
//            ->willReturn($total);
//
//        $this->request->setMethod('POST');
//
//        $this->form->expects($this->once())
//            ->method('submit')
//            ->with($this->request);
//
//        $this->form->expects($this->once())
//            ->method('isValid')
//            ->will($this->returnValue(true));
//
//        $this->manager->expects($this->once())
//            ->method('persist')
//            ->with($this->entity);
//
//        $this->manager->expects($this->once())
//            ->method('flush');
//
//        $this->assertTrue($this->handler->process($this->entity));
//
//        $propertyAccessor = PropertyAccess::createPropertyAccessor();
//        $this->assertEquals($subtotalAmount, $propertyAccessor->getValue($this->entity, $subtotal->getType()));
//        $this->assertEquals(
//            (float) $discountSubtotalAmount + $discountSubtotalAmount2,
//            $this->entity->getTotalDiscounts()->getValue()
//        );
//        $this->assertEquals($totalAmount, $propertyAccessor->getValue($this->entity, $total->getType()));
//    }
}
