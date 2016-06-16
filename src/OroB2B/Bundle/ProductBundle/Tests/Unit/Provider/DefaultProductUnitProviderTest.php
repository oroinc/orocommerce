<?php

namespace OroB2B\Bundle\ProductBundle\Tests\UnitProvider;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Provider\DefaultProductUnitProvider;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

class DefaultProductUnitProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DefaultProductUnitProvider
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

        $manager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $manager->expects($this->once())
            ->method('getRepository')
            ->with('OroB2B\Bundle\ProductBundle\Entity\ProductUnit')
            ->willReturn($productUnitRepository);

        $manager->expects($this->once())
            ->method('getRepository')
            ->with('OroB2B\Bundle\CatalogBundle\Entity\Category')
            ->willReturn($this->createMockCategoryRepository());

        $managerRegistry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->with('OroB2B\Bundle\ProductBundle\Entity\ProductUnit')
            ->willReturn($manager);

        $managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->with('OroB2B\Bundle\CatalogBundle\Entity\Category')
            ->willReturn($manager);


        $this->expectedUnitPrecision = new ProductUnitPrecision();
        $this->expectedUnitPrecision->setUnit($productUnit)->setPrecision('3');

        $this->defaultProductUnitProvider = new DefaultProductUnitProvider($configManager, $managerRegistry);
    }

    public function testGetDefaultProductUnit()
    {
        $this->assertEquals(
            $this->expectedUnitPrecision,
            $this->defaultProductUnitProvider->getDefaultProductUnitPrecision()
        );
    }

    private function createMockCategoryRepository()
    {
         $categoryRepository = $this
                    ->getMockBuilder('OroB2B\Bundle\CategoryBundle\Entity\Repository\CategoryRepository')
                    ->disableOriginalConstructor()
                    ->getMock();

         return $categoryRepository;
    }

}
