<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Apruve\Model\Order;

use Oro\Bundle\ApruveBundle\Apruve\Model\LineItem\ApruveLineItemInterface;
use Oro\Bundle\ApruveBundle\Apruve\Model\Order\ApruveOrder;

class ApruveOrderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ApruveOrder
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
        $lineItem = $this->createMock(ApruveLineItemInterface::class);
        $lineItem
            ->method('toArray')
            ->willReturn(['sku' => 'sampleSku']);

        $this->data = [
            ApruveOrder::MERCHANT_ID => 'sampleMerchantId',
            ApruveOrder::SHOPPER_ID => 'sampleShopperId',
            ApruveOrder::MERCHANT_ORDER_ID => 100,

            ApruveOrder::AMOUNT_CENTS => 10000,
            ApruveOrder::TAX_CENTS => 10,
            ApruveOrder::SHIPPING_CENTS => 10,
            ApruveOrder::CURRENCY => 'USD',
            ApruveOrder::LINE_ITEMS => [$lineItem],
            ApruveOrder::FINALIZE_ON_CREATE => true,
            ApruveOrder::INVOICE_ON_CREATE => false,
        ];

        $this->order = new ApruveOrder($this->data);
    }

    public function testGetters()
    {
        static::assertEquals($this->data[ApruveOrder::MERCHANT_ID], $this->order->getMerchantId());
        static::assertEquals($this->data[ApruveOrder::SHOPPER_ID], $this->order->getShopperId());
        static::assertEquals($this->data[ApruveOrder::MERCHANT_ORDER_ID], $this->order->getMerchantOrderId());
        static::assertEquals($this->data[ApruveOrder::AMOUNT_CENTS], $this->order->getAmountCents());
        static::assertEquals($this->data[ApruveOrder::TAX_CENTS], $this->order->getTaxCents());
        static::assertEquals($this->data[ApruveOrder::SHIPPING_CENTS], $this->order->getShippingCents());
        static::assertEquals($this->data[ApruveOrder::CURRENCY], $this->order->getCurrency());
        static::assertEquals($this->data[ApruveOrder::LINE_ITEMS], $this->order->getLineItems());
        static::assertEquals($this->data[ApruveOrder::FINALIZE_ON_CREATE], $this->order->getFinalizeOnCreate());
        static::assertEquals($this->data[ApruveOrder::INVOICE_ON_CREATE], $this->order->getInvoiceOnCreate());
    }

    public function testSetFinalizeOnCreate()
    {
        $order = new ApruveOrder();
        $order->setFinalizeOnCreate(true);

        static::assertTrue($order->getFinalizeOnCreate());
    }

    public function testSetInvoiceOnCreate()
    {
        $order = new ApruveOrder();
        $order->setInvoiceOnCreate(true);

        static::assertTrue($order->getInvoiceOnCreate());
    }

    public function testToArray()
    {
        $actual = $this->order->toArray();

        $expected = $this->data;
        $expected[ApruveOrder::LINE_ITEMS] = [['sku' => 'sampleSku']];

        static::assertSame($expected, $actual);
    }
}
