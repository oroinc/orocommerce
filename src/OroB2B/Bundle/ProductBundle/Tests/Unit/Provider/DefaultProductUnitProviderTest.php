<?php

namespace OroB2B\Bundle\ProductBundle\Tests\UnitProvider;

use OroB2B\Bundle\ProductBundle\Provider\DefaultProductUnitProvider;
use Oro\Bundle\ConfigBundle\Config\CongifManager;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

class DefaultProductUnitProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DefaultProductUnitProvider $defaultproductUnitProvider
     */
    protected $defaultProductUnitProvider;

    public function setUp()
    {
        $configManager = $this
            ->getMockBuilder(CongifManager::class)
            ->setMethods(array('get'))
            ->getMock();
        $map = array(
            array('orob2b_product.default_unit', 'kg'),
            array('orob2b_product.default_unit_precision', '3')
        );

        $configManager->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($map));

        $this->defaultProductUnitProvider = new DefaultProductUnitProvider($configManager);
    }

    public function testGetDefaultProductUnit()
    {
        $expectedUnit = new ProductUnit();
        $expectedUnit->setCode('kg');
        $expectedUnit->setDefaultPrecision(3);
        $this->assertEquals($expectedUnit, $this->defaultProductUnitProvider->getDefaultProductUnit());
    }
}

