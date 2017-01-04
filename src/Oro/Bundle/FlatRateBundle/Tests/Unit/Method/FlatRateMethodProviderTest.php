<?php

namespace Oro\Bundle\FlatRateBundle\Tests\Unit\Method;

use Oro\Bundle\FlatRateBundle\Method\FlatRateMethod;
use Oro\Bundle\FlatRateBundle\Method\FlatRateMethodProvider;

class FlatRateMethodProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FlatRateMethodProvider
     */
    protected $provider;

    public function setUp()
    {
        $this->provider = new FlatRateMethodProvider();
    }

    public function testGetShippingMethods()
    {
        $flatRate = new FlatRateMethod();
        $this->assertEquals([$flatRate->getIdentifier() => $flatRate], $this->provider->getShippingMethods());
    }

    public function testGetShippingMethod()
    {
        $flatRate = new FlatRateMethod();
        $this->assertEquals($flatRate, $this->provider->getShippingMethod($flatRate->getIdentifier()));
    }

    public function testHasShippingMethod()
    {
        $flatRate = new FlatRateMethod();
        $this->assertTrue($this->provider->hasShippingMethod($flatRate->getIdentifier()));
    }

    public function testHasShippingMethodFalse()
    {
        $this->assertFalse($this->provider->hasShippingMethod('wrong'));
    }
}
