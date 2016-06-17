<?php

namespace OroB2B\Bundle\ProductBundle\Tests\UnitProvider;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Provider\DefaultProductUnitProvider;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

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

    /**
     * @var ProductUnitPrecision
     */
    protected $expectedUnitPrecisionCategory1;

    public function setUp()
    {
        $configManager = $this
            ->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $map = [
            ['orob2b_product.default_unit', false, false, 'kg'],
            ['orob2b_product.default_unit_precision', false, false, '3']
        ];

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
        $this->expectedUnitPrecision = new ProductUnitPrecision();
        $this->expectedUnitPrecision->setUnit($productUnit)->setPrecision('3');

        $productUnitRepository->expects($this->any())
            ->method('findOneBy')
            ->will($this->returnValue($productUnit));

        $manager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');

        $mapRepository = [
            ['OroB2BProductBundle:ProductUnit', $productUnitRepository],
            ['OroB2BCatalogBundle:Category', $this->createMockCategoryRepository()]
        ];

        $manager->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValueMap($mapRepository));

        $managerRegistry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        $mapManager = [
            ['OroB2BProductBundle:ProductUnit', $manager],
            ['OroB2BCatalogBundle:Category', $manager]
        ];

        $managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->will($this->returnValueMap($mapManager));

        $this->expectedUnitPrecisionCategory1 = new ProductUnitPrecision();
        $productUnitCategory1 = new ProductUnit();
        $productUnitCategory1->setCode('set');
        $this->expectedUnitPrecisionCategory1->setUnit($productUnitCategory1)->setPrecision('2');
        $this->defaultProductUnitProvider = new DefaultProductUnitProvider($configManager, $managerRegistry);
    }

    public function testGetDefaultProductUnit()
    {
        /* when no category selected */
        $this->assertEquals(
            $this->expectedUnitPrecision,
            $this->defaultProductUnitProvider->getDefaultProductUnitPrecision()
        );

        /* when selected category has not parent no default unit*/
        $this->defaultProductUnitProvider->setCategoryId(3);
        $this->assertEquals(
            $this->expectedUnitPrecision,
            $this->defaultProductUnitProvider->getDefaultProductUnitPrecision()
        );

        /* when selected category has default unit*/
        $this->defaultProductUnitProvider->setCategoryId(1);
        $this->assertEquals(
            $this->expectedUnitPrecisionCategory1,
            $this->defaultProductUnitProvider->getDefaultProductUnitPrecision()
        );

        /* when selected category has no default unit but its parent category has one*/
        $this->defaultProductUnitProvider->setCategoryId(2);
        $this->assertEquals(
            $this->expectedUnitPrecisionCategory1,
            $this->defaultProductUnitProvider->getDefaultProductUnitPrecision()
        );
    }

    /**
     * @return  Mock_CategoryRepository
     */
    private function createMockCategoryRepository()
    {
        $categoryUnitPrecision = $this
            ->getMockBuilder('OroB2B\Bundle\CategoryBundle\Entity\Repository\CategoryUnitPrecision')
            ->setMethods(['getUnit', 'getPrecision'])
            ->getMock();

        $productUnit = new ProductUnit();
        $productUnit->setCode('set');

        $categoryUnitPrecision->expects($this->any())
            ->method('getUnit')
            ->will($this->returnValue($productUnit));

        $categoryUnitPrecision->expects($this->any())
            ->method('getPrecision')
            ->will($this->returnValue(2));

        $category1 = $this
            ->getMockBuilder('OroB2B\Bundle\CategoryBundle\Entity\Repository\Category')
            ->setMethods(['getUnitPrecision', 'getParentCategory'])
            ->getMock();
        $category1->expects($this->any())
            ->method('getUnitPrecision')
            ->willReturn($categoryUnitPrecision);

        $category2 = $this
            ->getMockBuilder('OroB2B\Bundle\CategoryBundle\Entity\Repository\Category')
            ->setMethods(['getUnitPrecision', 'getParentCategory'])
            ->getMock();
        $category2->expects($this->any())
            ->method('getUnitPrecision')
            ->willReturn(null);
        $category2->expects($this->any())
            ->method('getParentCategory')
            ->willReturn($category1);

        $category3 = $this
            ->getMockBuilder('OroB2B\Bundle\CategoryBundle\Entity\Repository\Category')
            ->setMethods(['getUnitPrecision', 'getParentCategory'])
            ->getMock();
        $category3->expects($this->any())
            ->method('getUnitPrecision')
            ->willReturn(null);
        $category3->expects($this->any())
            ->method('getParentCategory')
            ->willReturn(null);

        $categoryRepository = $this
            ->getMockBuilder('OroB2B\Bundle\CategoryBundle\Entity\Repository\CategoryRepository')
            ->setMethods(['findOneById'])
            ->getMock();

        $mapCategory = [
            [1, $category1],
            [2, $category2],
            [3, $category3],
        ];

        $categoryRepository->expects($this->any())
            ->method('findOneById')
            ->will($this->returnValueMap($mapCategory));

        return $categoryRepository;
    }
}
