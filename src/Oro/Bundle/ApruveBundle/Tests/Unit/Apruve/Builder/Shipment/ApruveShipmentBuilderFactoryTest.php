<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Apruve\Builder\Shipment;

use Oro\Bundle\ApruveBundle\Apruve\Builder\Shipment\ApruveShipmentBuilder;
use Oro\Bundle\ApruveBundle\Apruve\Builder\Shipment\ApruveShipmentBuilderFactory;

class ApruveShipmentBuilderFactoryTest extends \PHPUnit_Framework_TestCase
{
    const AMOUNT_CENTS = 11130;
    const CURRENCY = 'USD';
    const SHIPPED_AT_STRING = '2027-04-15T10:12:27-05:00';

    /**
     * @var ApruveShipmentBuilderFactory
     */
    private $factory;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        $this->factory = new ApruveShipmentBuilderFactory();
    }

    public function testCreate()
    {
        $actual = $this->factory->create(
            self::AMOUNT_CENTS,
            self::CURRENCY,
            self::SHIPPED_AT_STRING
        );

        $expected = new ApruveShipmentBuilder(
            self::AMOUNT_CENTS,
            self::CURRENCY,
            self::SHIPPED_AT_STRING
        );

        static::assertEquals($expected, $actual);
    }
}
