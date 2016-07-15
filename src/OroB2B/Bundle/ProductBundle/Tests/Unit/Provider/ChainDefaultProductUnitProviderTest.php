<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Provider;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Provider\ChainDefaultProductUnitProvider;
use OroB2B\Bundle\ProductBundle\Provider\DefaultProductUnitProviderInterface;

class ChainDefaultProductUnitProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ChainDefaultProductUnitProvider
     */
    protected $chainProvider;

    /**
     * @var DefaultProductUnitProviderInterface
     */
    protected $highPriorityProvider;

    /**
     * @var DefaultProductUnitProviderInterface
     */
    protected $lowPriorityProvider;

    /**
     * @var ProductUnitPrecision
     */
    protected $unitPrecision;

    protected function setUp()
    {
        $this->chainProvider = new ChainDefaultProductUnitProvider();
        $this->unitPrecision = new ProductUnitPrecision();

        $this->highPriorityProvider = $this
            ->getMockBuilder('OroB2B\Bundle\ProductBundle\Provider\DefaultProductUnitProviderInterface')
            ->disableOriginalConstructor()
            ->setMockClassName('HighPriorityProvider')
            ->getMock();
        $this->lowPriorityProvider = $this
            ->getMockBuilder('OroB2B\Bundle\ProductBundle\Provider\DefaultProductUnitProviderInterface')
            ->disableOriginalConstructor()
            ->setMockClassName('LowPriorityProvider')
            ->getMock();

        $this->chainProvider->addProvider($this->highPriorityProvider);
        $this->chainProvider->addProvider($this->lowPriorityProvider);
    }

    public function testGetDefaultProductUnitPrecisionByHighPriorityProvider()
    {
        $this->highPriorityProvider
            ->expects($this->once())
            ->method('getDefaultProductUnitPrecision')
            ->will($this->returnValue($this->unitPrecision));
        $this->lowPriorityProvider
            ->expects($this->never())
            ->method('getDefaultProductUnitPrecision');

        $this->assertEquals($this->unitPrecision, $this->chainProvider->getDefaultProductUnitPrecision());
    }

    public function testGetDefaultProductUnitPrecisionByLowPriorityProvider()
    {
        $this->highPriorityProvider
            ->expects($this->once())
            ->method('getDefaultProductUnitPrecision')
            ->will($this->returnValue(null));
        $this->lowPriorityProvider
            ->expects($this->once())
            ->method('getDefaultProductUnitPrecision')
            ->will($this->returnValue($this->unitPrecision));

        $this->assertEquals($this->unitPrecision, $this->chainProvider->getDefaultProductUnitPrecision());
    }

    public function testGetDefaultProductUnitPrecisionNone()
    {
        $this->highPriorityProvider
            ->expects($this->once())
            ->method('getDefaultProductUnitPrecision')
            ->will($this->returnValue(null));
        $this->lowPriorityProvider
            ->expects($this->once())
            ->method('getDefaultProductUnitPrecision')
            ->will($this->returnValue(null));

        $this->assertNull($this->chainProvider->getDefaultProductUnitPrecision());
    }
}
