<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Apruve\Model\Order;

use Oro\Bundle\ApruveBundle\Apruve\Model\LineItem\ApruveLineItemFactoryInterface;
use Oro\Bundle\ApruveBundle\Apruve\Model\LineItem\ApruveLineItemInterface;
use Oro\Bundle\ApruveBundle\Apruve\Model\Order\ApruveOrder;
use Oro\Bundle\ApruveBundle\Apruve\Model\Order\ApruveOrderFactory;
use Oro\Bundle\ApruveBundle\Apruve\Provider\SupportedCurrenciesProviderInterface;
use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;

class ApruveOrderFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ApruveConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $config;

    /**
     * @var ApruveLineItemFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $lineItemFactory;

    /**
     * @var ApruveOrder
     */
    private $apruveOrder;

    /**
     * @var SupportedCurrenciesProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $supportedCurrenciesProvider;

    /**
     * @var ApruveOrderFactory
     */
    private $testedFactory;

    /**
     * @var Order|\PHPUnit_Framework_MockObject_MockObject
     */
    private $order;

    /**
     * @var array
     */
    private $data;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $apruveLineItem = $this->createMock(ApruveLineItemInterface::class);
        $apruveLineItem
            ->method('toArray')
            ->willReturn(['sku' => 'sampleSku']);

        $this->data = [
            ApruveOrder::MERCHANT_ID => 'sampleMerchantId',
            ApruveOrder::MERCHANT_ORDER_ID => 100,
            ApruveOrder::AMOUNT_CENTS => 10000,
            ApruveOrder::SHIPPING_CENTS => 100,
            ApruveOrder::CURRENCY => 'USD',
            ApruveOrder::LINE_ITEMS => [$apruveLineItem],
        ];

        $this->apruveOrder = new ApruveOrder($this->data);

        /** @var Order|\PHPUnit_Framework_MockObject_MockObject $order */
        $this->order = $this->createMock(Order::class);

        $this->order
            ->method('getId')
            ->willReturn($this->data[ApruveOrder::MERCHANT_ORDER_ID]);
        $this->order
            ->method('getTotal')
            ->willReturn(100);

        $orderLineItem = $this->createMock(OrderLineItem::class);
        $this->order
            ->method('getLineItems')
            ->willReturn([$orderLineItem]);

        $this->supportedCurrenciesProvider = $this
            ->createMock(SupportedCurrenciesProviderInterface::class);
        $this->supportedCurrenciesProvider
            ->method('isSupported')
            ->willReturnMap([
                ['USD', true],
                ['EUR', false],
            ]);

        $this->lineItemFactory = $this->createMock(ApruveLineItemFactoryInterface::class);
        $this->lineItemFactory
            ->method('createFromOrderLineItem')
            ->with($orderLineItem)
            ->willReturn($apruveLineItem);

        $this->config = $this->createMock(ApruveConfigInterface::class);
        $this->config
            ->method('getMerchantId')
            ->willReturn($this->data[ApruveOrder::MERCHANT_ID]);

        $this->testedFactory = new ApruveOrderFactory($this->supportedCurrenciesProvider, $this->lineItemFactory);
    }

    public function testCreateFromOrder()
    {
        $this->order
            ->method('getCurrency')
            ->willReturn('USD');

        $price = $this->createMock(Price::class);
        $price
            ->method('getValue')
            ->willReturn(1);

        $this->order
            ->method('getShippingCost')
            ->willReturn($price);

        $actual = $this->testedFactory->createFromOrder($this->order, $this->config);

        static::assertEquals($this->apruveOrder, $actual);
    }

    public function testCreateFromOrderWithNullPrice()
    {
        $this->order
            ->method('getCurrency')
            ->willReturn('USD');

        $price = $this->createMock(Price::class);
        $price
            ->method('getValue')
            ->willReturn(null);

        $this->order
            ->method('getShippingCost')
            ->willReturn($price);

        $actual = $this->testedFactory->createFromOrder($this->order, $this->config);

        $this->data[ApruveOrder::SHIPPING_CENTS] = 0;
        $expected = new ApruveOrder($this->data);
        static::assertEquals($expected, $actual);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Currency EUR is not supported
     */
    public function testCreateFromOrderInvalidCurrency()
    {
        $this->order
            ->method('getCurrency')
            ->willReturn('EUR');

        $this->testedFactory->createFromOrder($this->order, $this->config);
    }
}
