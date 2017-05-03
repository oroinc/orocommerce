<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Apruve\Builder;

use Oro\Bundle\ApruveBundle\Apruve\Generator\OrderSecureHashGenerator;
use Oro\Bundle\ApruveBundle\Apruve\Generator\OrderSecureHashGeneratorInterface;
use Oro\Bundle\ApruveBundle\Apruve\Model\ApruveLineItem;
use Oro\Bundle\ApruveBundle\Apruve\Model\ApruveOrder;

class OrderSecureHashGeneratorTest extends \PHPUnit_Framework_TestCase
{
    const API_KEY = 'sampleApiKey';

    /**
     * @var ApruveOrder|\PHPUnit_Framework_MockObject_MockObject
     */
    private $apruveOrder;

    /**
     * @var OrderSecureHashGeneratorInterface
     */
    private $generator;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        $this->apruveOrder = $this->createMock(ApruveOrder::class);
        $this->generator = new OrderSecureHashGenerator();
    }

    /**
     * @dataProvider generateDataProvider
     *
     * @param array $data
     */
    public function testGenerate($data, $expectedHash)
    {
        $this->apruveOrder
            ->expects(static::once())
            ->method('getData')
            ->willReturn($data);

        $actual = $this->generator->generate($this->apruveOrder, self::API_KEY);

        static::assertSame($expectedHash, $actual);
    }

    /**
     * @return array
     */
    public function generateDataProvider()
    {
        return [
            'properly ordered data array with all possible parameters' => [
                [
                    ApruveOrder::MERCHANT_ID => 'sampleId',
                    ApruveOrder::AMOUNT_CENTS => 10000,
                    ApruveOrder::CURRENCY => 'USD',
                    ApruveOrder::MERCHANT_ORDER_ID => '10',
                    ApruveOrder::TAX_CENTS => '100',
                    ApruveOrder::SHIPPING_CENTS => '500',
                    ApruveOrder::EXPIRE_AT => '2017-07-15T10:12:27-05:00',
                    ApruveOrder::FINALIZE_ON_CREATE => true,
                    ApruveOrder::INVOICE_ON_CREATE => false,
                    ApruveOrder::LINE_ITEMS => [
                        [
                            ApruveLineItem::TITLE => 'Sample title',
                            ApruveLineItem::AMOUNT_CENTS => 10000,
                            ApruveLineItem::PRICE_EA_CENTS => 1000,
                            ApruveLineItem::QUANTITY => 10,
                            ApruveLineItem::MERCHANT_NOTES => "Merchant" . PHP_EOL . "notes",
                            ApruveLineItem::DESCRIPTION => "Sample" . PHP_EOL . "description with line break",
                            ApruveLineItem::VARIANT_INFO => 'yellow',
                            ApruveLineItem::SKU => 'sku1',
                            ApruveLineItem::VENDOR => 'ORO',
                            ApruveLineItem::VIEW_PRODUCT_URL => 'http://example.com/product/view/1'
                        ],
                    ],
                ],
                '9b286c88323757ce118839330e8b6598d07d7875af55c2dd308050d59d9d3140',
            ],
            'unordered data array with all possible parameters' => [
                [
                    ApruveOrder::CURRENCY => 'USD',
                    ApruveOrder::MERCHANT_ID => 'sampleId',
                    ApruveOrder::EXPIRE_AT => '2017-07-15T10:12:27-05:00',
                    ApruveOrder::AMOUNT_CENTS => 10000,
                    ApruveOrder::MERCHANT_ORDER_ID => '10',
                    ApruveOrder::TAX_CENTS => '100',
                    ApruveOrder::SHIPPING_CENTS => '500',
                    ApruveOrder::INVOICE_ON_CREATE => false,
                    ApruveOrder::FINALIZE_ON_CREATE => true,
                    ApruveOrder::LINE_ITEMS => [
                        [
                            ApruveLineItem::QUANTITY => 10,
                            ApruveLineItem::AMOUNT_CENTS => 10000,
                            ApruveLineItem::PRICE_EA_CENTS => 1000,
                            ApruveLineItem::SKU => 'sku1',
                            ApruveLineItem::DESCRIPTION => "Sample" . PHP_EOL . "description with line break",
                            ApruveLineItem::VIEW_PRODUCT_URL => 'http://example.com/product/view/1',
                            ApruveLineItem::TITLE => 'Sample title',
                            ApruveLineItem::MERCHANT_NOTES => "Merchant" . PHP_EOL . "notes",
                            ApruveLineItem::VARIANT_INFO => 'yellow',
                            ApruveLineItem::VENDOR => 'ORO',
                        ],
                    ],
                ],
                '9b286c88323757ce118839330e8b6598d07d7875af55c2dd308050d59d9d3140',
            ],
            'properly ordered data array with missing parameters' => [
                [
                    ApruveOrder::MERCHANT_ID => 'sampleId',
                    ApruveOrder::AMOUNT_CENTS => 10000,
                    ApruveOrder::CURRENCY => 'USD',
                    ApruveOrder::SHIPPING_CENTS => '500',
                    ApruveOrder::LINE_ITEMS => [
                        [
                            ApruveLineItem::TITLE => 'Sample title',
                            ApruveLineItem::AMOUNT_CENTS => 10000,
                            ApruveLineItem::QUANTITY => 10,
                            ApruveLineItem::DESCRIPTION => "Sample" . PHP_EOL . "description with line break",
                            ApruveLineItem::SKU => 'sku1',
                            ApruveLineItem::VIEW_PRODUCT_URL => 'http://example.com/product/view/1',
                        ],
                    ],
                ],
                '1e35db97342dd7362f0906caa9304d86c14337fab6619382e08a42b71eb72460',
            ],
            'unordered data array with missing parameters' => [
                [
                    ApruveOrder::SHIPPING_CENTS => '500',
                    ApruveOrder::MERCHANT_ID => 'sampleId',
                    ApruveOrder::AMOUNT_CENTS => 10000,
                    ApruveOrder::CURRENCY => 'USD',
                    ApruveOrder::LINE_ITEMS => [
                        [
                            ApruveLineItem::VIEW_PRODUCT_URL => 'http://example.com/product/view/1',
                            ApruveLineItem::AMOUNT_CENTS => 10000,
                            ApruveLineItem::SKU => 'sku1',
                            ApruveLineItem::QUANTITY => 10,
                            ApruveLineItem::TITLE => 'Sample title',
                            ApruveLineItem::DESCRIPTION => "Sample" . PHP_EOL . "description with line break",
                        ],
                    ],
                ],
                '1e35db97342dd7362f0906caa9304d86c14337fab6619382e08a42b71eb72460',
            ],
        ];
    }
}
