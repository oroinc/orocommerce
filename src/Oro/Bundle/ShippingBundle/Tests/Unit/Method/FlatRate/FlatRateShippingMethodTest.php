<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Method\FlatRate;

use Oro\Bundle\ShippingBundle\Method\FlatRate\FlatRateShippingMethod;
use Oro\Bundle\ShippingBundle\Method\FlatRate\FlatRateShippingMethodType;

class FlatRateShippingMethodTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FlatRateShippingMethod
     */
    protected $flatRate;

    protected function setUp()
    {
        $this->flatRate = new FlatRateShippingMethod();
    }

    public function testGetName()
    {
        static::assertEquals(FlatRateShippingMethod::IDENTIFIER, $this->flatRate->getIdentifier());
    }

    public function testIsGrouped()
    {
        static::assertTrue($this->flatRate->isGrouped());
    }

    public function testGetLabel()
    {
        static::assertEquals('oro.shipping.method.flat_rate.label', $this->flatRate->getLabel());
    }

    public function testGetTypes()
    {
        $types = $this->flatRate->getTypes();
        static::assertCount(1, $types);
        static::assertInstanceOf(FlatRateShippingMethodType::class, $types[0]);
    }

    public function testGetTypeNull()
    {
        static::assertNull($this->flatRate->getType(null));
    }

    public function testGetType()
    {
        $type = $this->flatRate->getType(FlatRateShippingMethodType::IDENTIFIER);
        static::assertInstanceOf(FlatRateShippingMethodType::class, $type);
    }

    public function testGetOptionsConfigurationFormType()
    {
        static::markTestIncomplete('Implement in BB-4287');
        static::assertEquals(
            FlatRateShippingMethodOptionsType::class,
            $this->flatRate->getOptionsConfigurationFormType()
        );
    }

    public function testGetSortOrder()
    {
        static::assertEquals(10, $this->flatRate->getSortOrder());
    }
}
