<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Apruve\Builder;

use Oro\Bundle\ApruveBundle\Apruve\Builder\ApruveLineItemBuilder;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItemInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class ApruveLineItemBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Mandatory
     */
    const PRODUCT_ID = 1;
    const PRODUCT_NAME = 'Sample name';
    const PRODUCT_DESCR = 'Sample description';
    const VIEW_PRODUCT_URL = 'http://example.com/product/view/1';
    const AMOUNT = '123.45';
    const QUANTITY = 100;
    const AMOUNT_CENTS = 12345;
    const CURRENCY = 'USD';
    const SKU = 'sku1';

    /**
     * Optional
     */
    const MERCHANT_NOTES = 'Sample note';
    const VENDOR = 'Sample vendor name';
    const AMOUNT_EA = '1.23';
    const AMOUNT_EA_CENTS = 123;

    /**
     * @var Product|\PHPUnit_Framework_MockObject_MockObject
     */
    private $product;

    /**
     * @var PaymentLineItemInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentLineItem;

    /**
     * @var RouterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $router;

    /**
     * @var ApruveLineItemBuilder
     */
    private $builder;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $price = $this->createMock(Price::class);
        $price
            ->method('getValue')
            ->willReturn(self::AMOUNT);
        $price
            ->method('getCurrency')
            ->willReturn(self::CURRENCY);

        $this->router = $this->createMock(RouterInterface::class);
        $this->router
            ->method('generate')
            ->with('oro_product_view', ['id' => self::PRODUCT_ID], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn(self::VIEW_PRODUCT_URL);

        $this->product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getName', 'getDescription'])
            ->getMock();
        $this->product
            ->method('getId')
            ->willReturn(self::PRODUCT_ID);
        $this->product
            ->method('getName')
            ->willReturn(self::PRODUCT_NAME);
        $this->product
            ->method('getDescription')
            ->willReturn(self::PRODUCT_DESCR);

        $this->paymentLineItem = $this->createMock(PaymentLineItemInterface::class);
        $this->paymentLineItem
            ->method('getPrice')
            ->willReturn($price);
        $this->paymentLineItem
            ->method('getQuantity')
            ->willReturn(self::QUANTITY);
        $this->paymentLineItem
            ->method('getProductSku')
            ->willReturn(self::SKU);
        $this->paymentLineItem
            ->method('getProduct')
            ->willReturn($this->product);

        $this->builder = new ApruveLineItemBuilder($this->paymentLineItem, $this->router);
    }

    public function testGetResult()
    {
        $actual = $this->builder->getResult();

        $expected = [
            ApruveLineItemBuilder::PRICE_TOTAL_CENTS => self::AMOUNT_CENTS,
            ApruveLineItemBuilder::QUANTITY => self::QUANTITY,
            ApruveLineItemBuilder::CURRENCY => self::CURRENCY,
            ApruveLineItemBuilder::SKU => self::SKU,
            ApruveLineItemBuilder::TITLE => self::PRODUCT_NAME,
            ApruveLineItemBuilder::DESCRIPTION => self::PRODUCT_DESCR,
            ApruveLineItemBuilder::VIEW_PRODUCT_URL => self::VIEW_PRODUCT_URL,
        ];
        static::assertSame($expected, $actual->getData());
    }

    public function testGetResultWithOptionalParams()
    {
        $this->builder->setMerchantNotes(self::MERCHANT_NOTES);
        $this->builder->setVendor(self::VENDOR);
        $this->builder->setAmountEa(self::AMOUNT_EA);

        $actual = $this->builder->getResult();

        $expected = [
            ApruveLineItemBuilder::PRICE_TOTAL_CENTS => self::AMOUNT_CENTS,
            ApruveLineItemBuilder::QUANTITY => self::QUANTITY,
            ApruveLineItemBuilder::CURRENCY => self::CURRENCY,
            ApruveLineItemBuilder::SKU => self::SKU,
            ApruveLineItemBuilder::TITLE => self::PRODUCT_NAME,
            ApruveLineItemBuilder::DESCRIPTION => self::PRODUCT_DESCR,
            ApruveLineItemBuilder::VIEW_PRODUCT_URL => self::VIEW_PRODUCT_URL,
            /**
             * Optional
             */
            ApruveLineItemBuilder::MERCHANT_NOTES => self::MERCHANT_NOTES,
            ApruveLineItemBuilder::VENDOR => self::VENDOR,
            ApruveLineItemBuilder::PRICE_EA_CENTS => self::AMOUNT_EA_CENTS,
        ];
        static::assertEquals($expected, $actual->getData());
    }
}
