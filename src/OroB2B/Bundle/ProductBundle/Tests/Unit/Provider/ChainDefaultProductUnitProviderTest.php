<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Provider;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Provider\ChainDefaultProductUnitProvider;

class ChainDefaultProductUnitProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ChainDefaultProductUnitProvider
     */
    protected $chainProvider;

    /**
     * @var array
     */
    protected $providers = [];

    /**
     * @var ProductUnitPrecision
     */
    protected $unitPrecision;

    protected function setUp()
    {
        $this->chainProvider = new ChainDefaultProductUnitProvider();
        $this->unitPrecision = new ProductUnitPrecision();

        $highPriorityProvider = $this
            ->getMockBuilder('OroB2B\Bundle\ProductBundle\Provider\AbstractDefaultProductUnitProvider')
            ->disableOriginalConstructor()
            ->setMockClassName('HighPriorityProvider')
            ->getMock();
        $lowPriorityProvider = $this
            ->getMockBuilder('OroB2B\Bundle\ProductBundle\Provider\AbstractDefaultProductUnitProvider')
            ->disableOriginalConstructor()
            ->setMockClassName('LowPriorityProvider')
            ->getMock();

        $this->chainProvider->addProvider($highPriorityProvider);
        $this->chainProvider->addProvider($lowPriorityProvider);

        $this->providers = [$highPriorityProvider, $lowPriorityProvider];
    }

    public function testGetDefaultProductUnitPrecisionByHighPriorityProvider()
    {
        $this->providers[0]
            ->expects($this->once())
            ->method('getDefaultProductUnitPrecision')
            ->will($this->returnValue($this->unitPrecision));
        $this->providers[1]
            ->expects($this->never())
            ->method('getDefaultProductUnitPrecision');

        $this->assertEquals($this->unitPrecision, $this->chainProvider->getDefaultProductUnitPrecision());
    }

    public function testGetDefaultProductUnitPrecisionByLowPriorityProvider()
    {
        $this->providers[0]
            ->expects($this->once())
            ->method('getDefaultProductUnitPrecision')
            ->will($this->returnValue(null));
        $this->providers[1]
            ->expects($this->once())
            ->method('getDefaultProductUnitPrecision')
            ->will($this->returnValue($this->unitPrecision));

        $this->assertEquals($this->unitPrecision, $this->chainProvider->getDefaultProductUnitPrecision());
    }

    public function testGetDefaultProductUnitPrecisionNone()
    {
        $this->providers[0]
            ->expects($this->once())
            ->method('getDefaultProductUnitPrecision')
            ->will($this->returnValue(null));
        $this->providers[1]
            ->expects($this->once())
            ->method('getDefaultProductUnitPrecision')
            ->will($this->returnValue(null));

        $this->assertNull($this->chainProvider->getDefaultProductUnitPrecision());
    }
}
