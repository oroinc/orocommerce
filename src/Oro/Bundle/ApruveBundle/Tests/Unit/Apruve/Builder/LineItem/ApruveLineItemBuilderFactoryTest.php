<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Apruve\Builder\LineItem;

use Oro\Bundle\ApruveBundle\Apruve\Builder\LineItem\ApruveLineItemBuilder;
use Oro\Bundle\ApruveBundle\Apruve\Builder\LineItem\ApruveLineItemBuilderFactory;

class ApruveLineItemBuilderFactoryTest extends \PHPUnit_Framework_TestCase
{
    const TITLE = 'Sample name';
    const AMOUNT_CENTS = 12345;
    const CURRENCY = 'USD';
    const QUANTITY = 10;

    /**
     * @var ApruveLineItemBuilderFactory
     */
    private $factory;


    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        $this->factory = new ApruveLineItemBuilderFactory();
    }

    public function testCreate()
    {
        $actual = $this->factory->create(
            self::TITLE,
            self::AMOUNT_CENTS,
            self::CURRENCY,
            self::QUANTITY
        );
        $expected = new ApruveLineItemBuilder(
            self::TITLE,
            self::AMOUNT_CENTS,
            self::CURRENCY,
            self::QUANTITY
        );

        static::assertEquals($expected, $actual);
    }
}
