<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Apruve\Builder\Invoice;

use Oro\Bundle\ApruveBundle\Apruve\Builder\Invoice\ApruveInvoiceBuilder;
use Oro\Bundle\ApruveBundle\Apruve\Builder\Invoice\ApruveInvoiceBuilderFactory;

class ApruveInvoiceBuilderFactoryTest extends \PHPUnit_Framework_TestCase
{
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
     * @var ApruveInvoiceBuilderFactory
     */
    private $factory;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        $this->factory = new ApruveInvoiceBuilderFactory();
    }

    public function testCreate()
    {
        $actual = $this->factory->create(
            self::AMOUNT_CENTS,
            self::CURRENCY,
            self::LINE_ITEMS
        );

        $expected = new ApruveInvoiceBuilder(
            self::AMOUNT_CENTS,
            self::CURRENCY,
            self::LINE_ITEMS
        );

        static::assertEquals($expected, $actual);
    }
}
