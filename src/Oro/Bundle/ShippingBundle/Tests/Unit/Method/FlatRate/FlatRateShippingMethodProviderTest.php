<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Method\FlatRate;

use Oro\Bundle\ShippingBundle\Method\FlatRate\FlatRateShippingMethod;
use Oro\Bundle\ShippingBundle\Method\FlatRate\FlatRateShippingMethodProvider;

class FlatRateShippingMethodProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FlatRateShippingMethodProvider
     */
    protected $provider;

    public function setUp()
    {
        $this->provider = new FlatRateShippingMethodProvider();
    }

    public function testGetShippingMethods()
    {
        $flatRate = new FlatRateShippingMethod();
        $this->assertEquals([$flatRate->getIdentifier() => $flatRate], $this->provider->getShippingMethods());
    }

    public function testGetShippingMethod()
    {
        $flatRate = new FlatRateShippingMethod();
        $this->assertEquals($flatRate, $this->provider->getShippingMethod($flatRate->getIdentifier()));
    }

    public function testHasShippingMethod()
    {
        $flatRate = new FlatRateShippingMethod();
        $this->assertTrue($this->provider->hasShippingMethod($flatRate->getIdentifier()));
    }

    public function testHasShippingMethodFalse()
    {
        $this->assertFalse($this->provider->hasShippingMethod('wrong'));
    }
}
