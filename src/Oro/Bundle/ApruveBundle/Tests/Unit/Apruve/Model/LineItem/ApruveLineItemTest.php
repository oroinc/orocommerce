<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Apruve\Model\LineItem;

use Oro\Bundle\ApruveBundle\Apruve\Model\LineItem\ApruveLineItem;

class ApruveLineItemTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ApruveLineItem
     */
    private $lineItem;

    /**
     * @var array
     */
    private $data;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->data = [
            ApruveLineItem::PRICE_TOTAL_CENTS => 10000,
            ApruveLineItem::PRICE_EA_CENTS => 100,
            ApruveLineItem::QUANTITY => 100,
            ApruveLineItem::CURRENCY => 'USD',
            ApruveLineItem::SKU => 'sampleSku',
            ApruveLineItem::TITLE => 'Sample Title',
            ApruveLineItem::DESCRIPTION => 'Sample Description',
            ApruveLineItem::VIEW_PRODUCT_URL => 'http://www.example.com',
            ApruveLineItem::VENDOR => 'Oro',
            ApruveLineItem::MERCHANT_NOTES => 'Sample notes',
        ];

        $this->lineItem = new ApruveLineItem($this->data);
    }

    public function testGetters()
    {
        static::assertEquals($this->data[ApruveLineItem::PRICE_TOTAL_CENTS], $this->lineItem->getPriceTotalCents());
        static::assertEquals($this->data[ApruveLineItem::PRICE_EA_CENTS], $this->lineItem->getPriceEaCents());
        static::assertEquals($this->data[ApruveLineItem::QUANTITY], $this->lineItem->getQuantity());
        static::assertEquals($this->data[ApruveLineItem::CURRENCY], $this->lineItem->getCurrency());
        static::assertEquals($this->data[ApruveLineItem::SKU], $this->lineItem->getSku());
        static::assertEquals($this->data[ApruveLineItem::TITLE], $this->lineItem->getTitle());
        static::assertEquals($this->data[ApruveLineItem::DESCRIPTION], $this->lineItem->getDescription());
        static::assertEquals($this->data[ApruveLineItem::VIEW_PRODUCT_URL], $this->lineItem->getViewProductUrl());
        static::assertEquals($this->data[ApruveLineItem::VENDOR], $this->lineItem->getVendor());
        static::assertEquals($this->data[ApruveLineItem::MERCHANT_NOTES], $this->lineItem->getMerchantNotes());
    }

    public function testToArray()
    {
        $actual = $this->lineItem->toArray();

        static::assertSame($this->data, $actual);
    }
}
