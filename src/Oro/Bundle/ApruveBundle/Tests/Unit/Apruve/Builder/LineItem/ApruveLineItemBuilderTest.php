<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Apruve\Builder\LineItem;

use Oro\Bundle\ApruveBundle\Apruve\Builder\LineItem\ApruveLineItemBuilder;
use Oro\Bundle\ApruveBundle\Apruve\Model\ApruveLineItem;

class ApruveLineItemBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Mandatory
     */
    const TITLE = 'Sample name';
    const AMOUNT_CENTS = 12345;
    const CURRENCY = 'USD';
    const QUANTITY = 10;

    /**
     * Optional
     */
    const SKU = 'sku1';
    const DESCRIPTION = 'Sample description';
    const AMOUNT_EA_CENTS = 1235;
    const VIEW_PRODUCT_URL = 'http://example.com/product/view/1';
    const MERCHANT_NOTES = 'Sample note';
    const VENDOR = 'Sample vendor name';
    const VARIANT_INFO = 'Sample variant';

    /**
     * @var ApruveLineItemBuilder
     */
    private $builder;

    protected function setUp()
    {
        $this->builder = new ApruveLineItemBuilder(
            self::TITLE,
            self::AMOUNT_CENTS,
            self::QUANTITY,
            self::CURRENCY
        );
    }


    public function testGetResult()
    {
        $actual = $this->builder->getResult();

        $expected = [
            ApruveLineItem::AMOUNT_CENTS => self::AMOUNT_CENTS,
            ApruveLineItem::PRICE_TOTAL_CENTS => self::AMOUNT_CENTS,
            ApruveLineItem::QUANTITY => self::QUANTITY,
            ApruveLineItem::CURRENCY => self::CURRENCY,
            ApruveLineItem::TITLE => self::TITLE,
        ];

        static::assertEquals($expected, $actual->getData());
    }

    public function testGetResultWithOptionalParams()
    {
        $this->builder->setSku(self::SKU);
        $this->builder->setDescription(self::DESCRIPTION);
        $this->builder->setViewProductUrl(self::VIEW_PRODUCT_URL);
        $this->builder->setMerchantNotes(self::MERCHANT_NOTES);
        $this->builder->setVendor(self::VENDOR);
        $this->builder->setEaCents(self::AMOUNT_EA_CENTS);
        $this->builder->setVariantInfo(self::VARIANT_INFO);

        $actual = $this->builder->getResult();

        $expected = [
            // Mandatory params.
            ApruveLineItem::TITLE => self::TITLE,
            ApruveLineItem::AMOUNT_CENTS => self::AMOUNT_CENTS,
            ApruveLineItem::PRICE_TOTAL_CENTS => self::AMOUNT_CENTS,
            ApruveLineItem::CURRENCY => self::CURRENCY,
            ApruveLineItem::QUANTITY => self::QUANTITY,

            // Optional params.
            ApruveLineItem::SKU => self::SKU,
            ApruveLineItem::DESCRIPTION => self::DESCRIPTION,
            ApruveLineItem::VIEW_PRODUCT_URL => self::VIEW_PRODUCT_URL,
            ApruveLineItem::MERCHANT_NOTES => self::MERCHANT_NOTES,
            ApruveLineItem::VENDOR => self::VENDOR,
            ApruveLineItem::PRICE_EA_CENTS => self::AMOUNT_EA_CENTS,
            ApruveLineItem::VARIANT_INFO => self::VARIANT_INFO,
        ];

        static::assertEquals($expected, $actual->getData());
    }
}
