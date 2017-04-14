<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Apruve\Builder;

use Oro\Bundle\ApruveBundle\Apruve\Generator\OrderSecureHashGenerator;
use Oro\Bundle\ApruveBundle\Apruve\Generator\OrderSecureHashGeneratorInterface;
use Oro\Bundle\ApruveBundle\Apruve\Model\Order\ApruveOrderInterface;
use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;

class OrderSecureHashGeneratorTest extends \PHPUnit_Framework_TestCase
{
    const API_KEY = 'sampleApiKey';

    const DATA = [
        // Properly ordered data array with all possible parameters.
        'variant1' => [
            OrderSecureHashGenerator::ORDER_MERCHANT_ID => 'sampleId',
            OrderSecureHashGenerator::ORDER_AMOUNT_CENTS => 10000,
            OrderSecureHashGenerator::ORDER_CURRENCY => 'USD',
            OrderSecureHashGenerator::ORDER_MERCHANT_ORDER_ID => '10',
            OrderSecureHashGenerator::ORDER_TAX_CENTS => '100',
            OrderSecureHashGenerator::ORDER_SHIPPING_CENTS => '500',
            OrderSecureHashGenerator::ORDER_EXPIRE_AT => '2017-07-15T10:12:27-05:00',
            OrderSecureHashGenerator::ORDER_ACCEPTS_PT => true,
            OrderSecureHashGenerator::ORDER_FINALIZE_ON_CREATE => true,
            OrderSecureHashGenerator::ORDER_INVOICE_ON_CREATE => false,
            OrderSecureHashGenerator::ORDER_LINE_ITEMS => [
                [
                    OrderSecureHashGenerator::LINE_ITEM_TITLE => 'Sample title',
                    OrderSecureHashGenerator::LINE_ITEM_PRICE_TOTAL_CENTS => 10000,
                    OrderSecureHashGenerator::LINE_ITEM_PRICE_EA_CENTS => 1000,
                    OrderSecureHashGenerator::LINE_ITEM_QUANTITY => 10,
                    OrderSecureHashGenerator::LINE_ITEM_MERCHANT_NOTES => "Merchant".PHP_EOL."notes",
                    OrderSecureHashGenerator::LINE_ITEM_DESCRIPTION => "Sample".PHP_EOL."description with line break",
                    OrderSecureHashGenerator::LINE_ITEM_VARIANT_INFO => 'yellow',
                    OrderSecureHashGenerator::LINE_ITEM_SKU => 'sku1',
                    OrderSecureHashGenerator::LINE_ITEM_VENDOR => 'ORO',
                    OrderSecureHashGenerator::LINE_ITEM_VIEW_PRODUCT_URL => 'http://example.com/product/view/1'
                ],
            ],
        ],
        // Unordered data array with all possible parameters.
        'variant2' => [
            OrderSecureHashGenerator::ORDER_CURRENCY => 'USD',
            OrderSecureHashGenerator::ORDER_MERCHANT_ID => 'sampleId',
            OrderSecureHashGenerator::ORDER_EXPIRE_AT => '2017-07-15T10:12:27-05:00',
            OrderSecureHashGenerator::ORDER_AMOUNT_CENTS => 10000,
            OrderSecureHashGenerator::ORDER_MERCHANT_ORDER_ID => '10',
            OrderSecureHashGenerator::ORDER_TAX_CENTS => '100',
            OrderSecureHashGenerator::ORDER_SHIPPING_CENTS => '500',
            OrderSecureHashGenerator::ORDER_ACCEPTS_PT => true,
            OrderSecureHashGenerator::ORDER_INVOICE_ON_CREATE => false,
            OrderSecureHashGenerator::ORDER_FINALIZE_ON_CREATE => true,
            OrderSecureHashGenerator::ORDER_LINE_ITEMS => [
                [
                    OrderSecureHashGenerator::LINE_ITEM_QUANTITY => 10,
                    OrderSecureHashGenerator::LINE_ITEM_PRICE_TOTAL_CENTS => 10000,
                    OrderSecureHashGenerator::LINE_ITEM_PRICE_EA_CENTS => 1000,
                    OrderSecureHashGenerator::LINE_ITEM_SKU => 'sku1',
                    OrderSecureHashGenerator::LINE_ITEM_DESCRIPTION => "Sample".PHP_EOL."description with line break",
                    OrderSecureHashGenerator::LINE_ITEM_VIEW_PRODUCT_URL => 'http://example.com/product/view/1',
                    OrderSecureHashGenerator::LINE_ITEM_TITLE => 'Sample title',
                    OrderSecureHashGenerator::LINE_ITEM_MERCHANT_NOTES => "Merchant".PHP_EOL."notes",
                    OrderSecureHashGenerator::LINE_ITEM_VARIANT_INFO => 'yellow',
                    OrderSecureHashGenerator::LINE_ITEM_VENDOR => 'ORO',
                ],
            ],
        ],
        // Properly ordered data array with missing parameters.
        'variant3' => [
            OrderSecureHashGenerator::ORDER_MERCHANT_ID => 'sampleId',
            OrderSecureHashGenerator::ORDER_AMOUNT_CENTS => 10000,
            OrderSecureHashGenerator::ORDER_CURRENCY => 'USD',
            OrderSecureHashGenerator::ORDER_SHIPPING_CENTS => '500',
            OrderSecureHashGenerator::ORDER_FINALIZE_ON_CREATE => true,
            OrderSecureHashGenerator::ORDER_INVOICE_ON_CREATE => false,
            OrderSecureHashGenerator::ORDER_LINE_ITEMS => [
                [
                    OrderSecureHashGenerator::LINE_ITEM_TITLE => 'Sample title',
                    OrderSecureHashGenerator::LINE_ITEM_PRICE_TOTAL_CENTS => 10000,
                    OrderSecureHashGenerator::LINE_ITEM_QUANTITY => 10,
                    OrderSecureHashGenerator::LINE_ITEM_DESCRIPTION => "Sample".PHP_EOL."description with line break",
                    OrderSecureHashGenerator::LINE_ITEM_SKU => 'sku1',
                    OrderSecureHashGenerator::LINE_ITEM_VIEW_PRODUCT_URL => 'http://example.com/product/view/1',
                ],
            ],
        ],
        // Unordered data array with missing parameters.
        'variant4' => [
            OrderSecureHashGenerator::ORDER_FINALIZE_ON_CREATE => true,
            OrderSecureHashGenerator::ORDER_SHIPPING_CENTS => '500',
            OrderSecureHashGenerator::ORDER_MERCHANT_ID => 'sampleId',
            OrderSecureHashGenerator::ORDER_AMOUNT_CENTS => 10000,
            OrderSecureHashGenerator::ORDER_CURRENCY => 'USD',
            OrderSecureHashGenerator::ORDER_INVOICE_ON_CREATE => false,
            OrderSecureHashGenerator::ORDER_LINE_ITEMS => [
                [
                    OrderSecureHashGenerator::LINE_ITEM_VIEW_PRODUCT_URL => 'http://example.com/product/view/1',
                    OrderSecureHashGenerator::LINE_ITEM_PRICE_TOTAL_CENTS => 10000,
                    OrderSecureHashGenerator::LINE_ITEM_SKU => 'sku1',
                    OrderSecureHashGenerator::LINE_ITEM_QUANTITY => 10,
                    OrderSecureHashGenerator::LINE_ITEM_TITLE => 'Sample title',
                    OrderSecureHashGenerator::LINE_ITEM_DESCRIPTION => "Sample".PHP_EOL."description with line break",
                ],
            ],
        ],
    ];

    const HASH = [
        'variant1' => 'b6543804da744868433f5d991e77e618136a3a66397e69bfd8043af41aa37388',
        'variant2' => 'b6543804da744868433f5d991e77e618136a3a66397e69bfd8043af41aa37388',
        'variant3' => '6c6b4a10f9afc452a065051ff42da575264d937f77888b4397fd85d0c12d2109',
        'variant4' => '6c6b4a10f9afc452a065051ff42da575264d937f77888b4397fd85d0c12d2109',
    ];

    /**
     * @var ApruveConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $apruveConfig;

    /**
     * @var ApruveOrderInterface|\PHPUnit_Framework_MockObject_MockObject
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
        $this->apruveOrder = $this->createMock(ApruveOrderInterface::class);
        $this->apruveConfig = $this->createMock(ApruveConfigInterface::class);
        $this->apruveConfig
            ->method('getApiKey')
            ->willReturn(self::API_KEY);

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
            ->method('getData')
            ->willReturn($data);
        $actual = $this->generator->generate($this->apruveOrder, $this->apruveConfig);

        static::assertSame($expectedHash, $actual);
    }

    /**
     * @return array
     */
    public function generateDataProvider()
    {
        return [
            // Must generate the same hash.
            'properly ordered data array with all possible parameters' => [self::DATA['variant1'], self::HASH['variant1']],
            'unordered data array with all possible parameters' =>  [self::DATA['variant2'], self::HASH['variant2']],

            // Must generate the same hash.
            'properly ordered data array with missing parameters' => [self::DATA['variant3'], self::HASH['variant3']],
            'unordered data array with missing parameters' => [self::DATA['variant4'], self::HASH['variant4']],
        ];
    }
}
