<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Handler;

use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Handler\OrderCurrencyHandler;

class OrderCurrencyHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var CurrencyProviderInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $currencyConfig;

    /**
     * @var OrderCurrencyHandler
     */
    protected $handler;

    protected function setUp()
    {
        $this->currencyConfig = $this->getMockBuilder(CurrencyProviderInterface::class)
            ->setMethods(['getDefaultCurrency'])
            ->getMockForAbstractClass() ;

        $this->handler = new OrderCurrencyHandler($this->currencyConfig);
    }

    public function testSetOrderCurrency()
    {
        $currency = 'USD';
        $this->currencyConfig->expects($this->once())
            ->method('getDefaultCurrency')
            ->willReturn($currency);

        $order = new Order();
        $this->handler->setOrderCurrency($order);
        $this->assertEquals($currency, $order->getCurrency());
    }
}
