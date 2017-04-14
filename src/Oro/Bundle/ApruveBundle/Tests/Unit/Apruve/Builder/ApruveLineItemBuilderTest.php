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
    const AMOUNT = '123.45';
    const QUANTITY = 10;
    const AMOUNT_CENTS = 12345;
    const AMOUNT_EA = '12.35';
    const AMOUNT_EA_CENTS = 1235;
    const SKU = 'sku1';
    const LINE_ITEM_SKU = 'lineItemSku1';
    const CURRENCY = 'USD';

    /**
     * Optional/customizable
     */
    const PRODUCT_NAME = 'Sample name';
    const PRODUCT_DESCR = 'Sample description with'.PHP_EOL.'line breaks and <div>tags</div>';
    const PRODUCT_DESCR_SANITIZED = 'Sample description with line breaks and tags';
    const VIEW_PRODUCT_URL = 'http://example.com/product/view/1';
    const MERCHANT_NOTES = 'Sample note';
    const VENDOR = 'Sample vendor name';
    const VARIANT_INFO = 'Sample variant';
    const CUSTOM_TITLE = 'Custom line item title';
    const CUSTOM_DESCR = 'Custom line item description with'.PHP_EOL.'line breaks and <div>tags</div>';
    const CUSTOM_DESCR_SANITIZED = 'Custom line item description with line breaks and tags';
    const CUSTOM_URL = 'http://example.com/custom-product-url';

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
            ->with('oro_product_frontend_product_view', ['id' => self::PRODUCT_ID], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn(self::VIEW_PRODUCT_URL);

        $this->paymentLineItem = $this->createMock(PaymentLineItemInterface::class);
        $this->paymentLineItem
            ->method('getPrice')
            ->willReturn($price);
        $this->paymentLineItem
            ->method('getQuantity')
            ->willReturn(self::QUANTITY);

        $this->builder = new ApruveLineItemBuilder($this->paymentLineItem, $this->router);
    }

    /**
     * @dataProvider getResultDataProvider
     *
     * @param string $lineItemSku
     * @param Product|\PHPUnit_Framework_MockObject_MockObject $product
     * @param array $expected
     */
    public function testGetResult($lineItemSku, $product, $expected)
    {
        $this->paymentLineItem
            ->method('getProductSku')
            ->willReturn($lineItemSku);

        $this->paymentLineItem
            ->method('getProduct')
            ->willReturn($product);

        $actual = $this->builder->getResult();

        static::assertEquals($expected, $actual->getData());
    }

    /**
     * @return array
     */
    public function getResultDataProvider()
    {
        return [
            'if product exists and line item sku is not null' => [
                'lineItemSku' => self::LINE_ITEM_SKU,
                'product' => $this->createProduct(),
                'expected' => [
                    ApruveLineItemBuilder::AMOUNT_CENTS => self::AMOUNT_CENTS,
                    ApruveLineItemBuilder::PRICE_EA_CENTS => self::AMOUNT_EA_CENTS,
                    ApruveLineItemBuilder::QUANTITY => self::QUANTITY,
                    ApruveLineItemBuilder::CURRENCY => self::CURRENCY,
                    ApruveLineItemBuilder::SKU => self::LINE_ITEM_SKU,
                    ApruveLineItemBuilder::TITLE => self::PRODUCT_NAME,
                    ApruveLineItemBuilder::DESCRIPTION => self::PRODUCT_DESCR_SANITIZED,
                    ApruveLineItemBuilder::VIEW_PRODUCT_URL => self::VIEW_PRODUCT_URL,
                ],
            ],
            // If product is empty, then sku stands for title; description and url are missing.
            'if product is empty' => [
                'lineItemSku' => self::LINE_ITEM_SKU,
                'product' => null,
                'expected' => [
                    ApruveLineItemBuilder::AMOUNT_CENTS => self::AMOUNT_CENTS,
                    ApruveLineItemBuilder::PRICE_EA_CENTS => self::AMOUNT_EA_CENTS,
                    ApruveLineItemBuilder::QUANTITY => self::QUANTITY,
                    ApruveLineItemBuilder::CURRENCY => self::CURRENCY,
                    ApruveLineItemBuilder::SKU => self::LINE_ITEM_SKU,
                    ApruveLineItemBuilder::TITLE => self::LINE_ITEM_SKU,
                ],
            ],
            'if line item sku is empty, then Product::sku is used' => [
                'lineItemSku' => null,
                'product' => $this->createProduct(),
                'expected' => [
                    ApruveLineItemBuilder::AMOUNT_CENTS => self::AMOUNT_CENTS,
                    ApruveLineItemBuilder::PRICE_EA_CENTS => self::AMOUNT_EA_CENTS,
                    ApruveLineItemBuilder::QUANTITY => self::QUANTITY,
                    ApruveLineItemBuilder::CURRENCY => self::CURRENCY,
                    ApruveLineItemBuilder::SKU => self::SKU,
                    ApruveLineItemBuilder::TITLE => self::PRODUCT_NAME,
                    ApruveLineItemBuilder::DESCRIPTION => self::PRODUCT_DESCR_SANITIZED,
                    ApruveLineItemBuilder::VIEW_PRODUCT_URL => self::VIEW_PRODUCT_URL,
                ],
            ],
        ];
    }

    public function testGetResultWithOptionalParams()
    {
        $this->paymentLineItem
            ->method('getProduct')
            ->willReturn($this->createProduct());

        $this->builder->setMerchantNotes(self::MERCHANT_NOTES);
        $this->builder->setVendor(self::VENDOR);
        $this->builder->setVariantInfo(self::VARIANT_INFO);

        $actual = $this->builder->getResult();

        $expected = [
            ApruveLineItemBuilder::AMOUNT_CENTS => self::AMOUNT_CENTS,
            ApruveLineItemBuilder::QUANTITY => self::QUANTITY,
            ApruveLineItemBuilder::CURRENCY => self::CURRENCY,
            ApruveLineItemBuilder::SKU => self::SKU,
            ApruveLineItemBuilder::TITLE => self::PRODUCT_NAME,
            ApruveLineItemBuilder::DESCRIPTION => self::PRODUCT_DESCR_SANITIZED,
            ApruveLineItemBuilder::VIEW_PRODUCT_URL => self::VIEW_PRODUCT_URL,
            // Optional params.
            ApruveLineItemBuilder::MERCHANT_NOTES => self::MERCHANT_NOTES,
            ApruveLineItemBuilder::VENDOR => self::VENDOR,
            ApruveLineItemBuilder::PRICE_EA_CENTS => self::AMOUNT_EA_CENTS,
            ApruveLineItemBuilder::VARIANT_INFO => self::VARIANT_INFO,
        ];
        static::assertEquals($expected, $actual->getData());
    }

    public function testGetResultWithCustomizedParams()
    {
        $this->paymentLineItem
            ->method('getProduct')
            ->willReturn($this->createProduct());

        $this->builder->setTitle(self::CUSTOM_TITLE);
        $this->builder->setDescription(self::CUSTOM_DESCR);
        $this->builder->setViewProductUrl(self::CUSTOM_URL);
        $this->builder->setMerchantNotes(self::MERCHANT_NOTES);
        $this->builder->setVendor(self::VENDOR);
        $this->builder->setVariantInfo(self::VARIANT_INFO);

        $actual = $this->builder->getResult();

        $expected = [
            ApruveLineItemBuilder::AMOUNT_CENTS => self::AMOUNT_CENTS,
            ApruveLineItemBuilder::QUANTITY => self::QUANTITY,
            ApruveLineItemBuilder::CURRENCY => self::CURRENCY,
            ApruveLineItemBuilder::SKU => self::SKU,
            // Customized params.
            ApruveLineItemBuilder::TITLE => self::CUSTOM_TITLE,
            ApruveLineItemBuilder::DESCRIPTION => self::CUSTOM_DESCR_SANITIZED,
            ApruveLineItemBuilder::VIEW_PRODUCT_URL => self::CUSTOM_URL,
            // Optional params.
            ApruveLineItemBuilder::MERCHANT_NOTES => self::MERCHANT_NOTES,
            ApruveLineItemBuilder::VENDOR => self::VENDOR,
            ApruveLineItemBuilder::PRICE_EA_CENTS => self::AMOUNT_EA_CENTS,
            ApruveLineItemBuilder::VARIANT_INFO => self::VARIANT_INFO,
        ];
        static::assertEquals($expected, $actual->getData());
    }

    /**
     * @return Product|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createProduct()
    {
        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getName', 'getDescription', 'getSku'])
            ->getMock();
        $product
            ->method('getId')
            ->willReturn(self::PRODUCT_ID);
        $product
            ->method('getName')
            ->willReturn(self::PRODUCT_NAME);
        $product
            ->method('getDescription')
            ->willReturn(self::PRODUCT_DESCR);
        $product
            ->method('getSku')
            ->willReturn(self::SKU);

        return $product;
    }
}
