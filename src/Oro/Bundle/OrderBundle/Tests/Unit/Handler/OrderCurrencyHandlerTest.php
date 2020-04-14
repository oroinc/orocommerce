<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Handler;

use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Handler\OrderCurrencyHandler;

class OrderCurrencyHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var CurrencyProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $currencyProvider;

    /**
     * @var OrderCurrencyHandler
     */
    protected $handler;

    protected function setUp(): void
    {
        $this->currencyProvider = $this->getMockBuilder(CurrencyProviderInterface::class)
            ->setMethods(['getDefaultCurrency'])
            ->getMockForAbstractClass() ;

        $this->handler = new OrderCurrencyHandler($this->currencyProvider);
    }

    public function testSetOrderCurrency()
    {
        $currency = 'USD';
        $this->currencyProvider->expects($this->once())
            ->method('getDefaultCurrency')
            ->willReturn($currency);

        $order = new Order();
        $this->handler->setOrderCurrency($order);
        $this->assertEquals($currency, $order->getCurrency());
    }
}
