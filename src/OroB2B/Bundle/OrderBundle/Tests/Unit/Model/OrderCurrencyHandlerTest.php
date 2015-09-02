<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Model;

use Oro\Bundle\LocaleBundle\Model\LocaleSettings;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Model\OrderCurrencyHandler;

class OrderCurrencyHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var LocaleSettings|\PHPUnit_Framework_MockObject_MockObject */
    protected $localeSettings;

    /**
     * @var OrderCurrencyHandler
     */
    protected $handler;

    protected function setUp()
    {
        $this->localeSettings = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Model\LocaleSettings')
            ->disableOriginalConstructor()
            ->getMock();

        $this->handler = new OrderCurrencyHandler($this->localeSettings);
    }

    public function testSetOrderCurrency()
    {
        $currency = 'USD';
        $this->localeSettings->expects($this->once())
            ->method('getCurrency')
            ->willReturn($currency);

        $order = new Order();
        $this->handler->setOrderCurrency($order);
        $this->assertEquals($currency, $order->getCurrency());
    }
}
