<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Provider\SystemDefaultProductUnitProvider;

class SystemDefaultProductUnitProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SystemDefaultProductUnitProvider
     */
    protected $defaultProductUnitProvider;

    /**
     * @var ProductUnitPrecision
     */
    protected $expectedUnitPrecision;

    public function setUp()
    {
        $configManager = $this
            ->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        
        $map = array(
            array('orob2b_product.default_unit', false, false, null, 'kg'),
            array('orob2b_product.default_unit_precision', false, false, null, '3')
        );

        $configManager->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($map));

        $productUnitRepository = $this
            ->getMockBuilder('Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $productUnit = new ProductUnit();
        $productUnit->setCode('kg');
        $productUnit->setDefaultPrecision('3');
        
        $productUnitRepository->expects($this->once())
            ->method('findOneBy')
            ->will($this->returnValue($productUnit));

        $manager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $manager->expects($this->once())
            ->method('getRepository')
            ->with('OroProductBundle:ProductUnit')
            ->willReturn($productUnitRepository);

        $managerRegistry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->with('OroProductBundle:ProductUnit')
            ->willReturn($manager);

        $this->expectedUnitPrecision = new ProductUnitPrecision();
        $this->expectedUnitPrecision->setUnit($productUnit)->setPrecision('3');

        $this->defaultProductUnitProvider = new SystemDefaultProductUnitProvider($configManager, $managerRegistry);
    }

    public function testGetDefaultProductUnit()
    {
        $this->assertEquals(
            $this->expectedUnitPrecision,
            $this->defaultProductUnitProvider->getDefaultProductUnitPrecision()
        );
    }
}
