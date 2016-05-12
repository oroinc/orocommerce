<?php

namespace OroB2B\Bundle\ProductBundle\Tests\UnitProvider;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Provider\DefaultProductUnitProvider;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

class DefaultProductUnitProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DefaultProductUnitProvider $defaultProductUnitProvider
     */
    protected $defaultProductUnitProvider;

    protected $expectedUnitPrecision;

    public function setUp()
    {
        $configManager = $this
            ->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        
        $map = array(
            array('orob2b_product.default_unit', false, false, 'kg'),
            array('orob2b_product.default_unit_precision', false, false, '3')
        );

        $configManager->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($map));

        $productUnitRepository = $this
            ->getMockBuilder('OroB2B\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $productUnit = new ProductUnit();
        $productUnit->setCode('kg');
        $productUnit->setDefaultPrecision('3');
        
        
        $productUnitRepository->expects($this->once())
            ->method('findOneBy')
            ->will($this->returnValue($productUnit));

        $entityManager = $this
            ->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($productUnitRepository));


        $this->expectedUnitPrecision = new ProductUnitPrecision();
        $this->expectedUnitPrecision->setUnit($productUnit)->setPrecision('3');

        $this->defaultProductUnitProvider = new DefaultProductUnitProvider($configManager, $entityManager);
    }

    public function testGetDefaultProductUnit()
    {
        $this->assertEquals(
            $this->expectedUnitPrecision,
            $this->defaultProductUnitProvider->getDefaultProductUnitPrecision()
        );
    }
}
