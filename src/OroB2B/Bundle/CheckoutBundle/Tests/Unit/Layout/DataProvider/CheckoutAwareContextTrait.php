<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\Layout\DataProvider;

use Oro\Component\Layout\ContextInterface;
use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;

trait CheckoutAwareContextTrait
{
    /**
     * @param Checkout $checkout
     * @return \PHPUnit_Framework_MockObject_MockObject|ContextInterface
     */
    protected function prepareContext(Checkout $checkout)
    {
        $context = $this->getMock('Oro\Component\Layout\ContextInterface');

        $data = $this->getMockBuilder('Oro\Component\Layout\ContextDataCollection')
            ->disableOriginalConstructor()
            ->getMock();

        $data->expects($this->once())
            ->method('get')
            ->with('checkout')
            ->will($this->returnValue($checkout));
        $context->expects($this->once())
            ->method('data')
            ->will($this->returnValue($data));

        return $context;
    }
}
