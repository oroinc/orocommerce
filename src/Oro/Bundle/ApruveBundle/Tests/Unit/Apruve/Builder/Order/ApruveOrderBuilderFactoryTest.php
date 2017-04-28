<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Apruve\Builder\Order;

use Oro\Bundle\ApruveBundle\Apruve\Builder\Order\ApruveOrderBuilder;
use Oro\Bundle\ApruveBundle\Apruve\Builder\Order\ApruveOrderBuilderFactory;

class ApruveOrderBuilderFactoryTest extends \PHPUnit_Framework_TestCase
{
    const MERCHANT_ID = 'sampleMerchantId';
    const AMOUNT_CENTS = 11130;
    const CURRENCY = 'USD';
    const LINE_ITEMS = [
        'sku1' => [
            'sku' => 'sku1',
            'quantity' => 100,
            'currency' => 'USD',
            'amount_cents' => 2000,
        ],
        'sku2' => [
            'sku' => 'sku2',
            'quantity' => 50,
            'currency' => 'USD',
            'amount_cents' => 1000,
        ],
    ];

    /**
     * @var ApruveOrderBuilderFactory
     */
    private $factory;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        $this->factory = new ApruveOrderBuilderFactory();
    }

    public function testCreate()
    {
        $actual = $this->factory->create(
            self::MERCHANT_ID,
            self::AMOUNT_CENTS,
            self::CURRENCY,
            self::LINE_ITEMS
        );

        $expected = new ApruveOrderBuilder(
            self::MERCHANT_ID,
            self::AMOUNT_CENTS,
            self::CURRENCY,
            self::LINE_ITEMS
        );

        static::assertEquals($expected, $actual);
    }
}
