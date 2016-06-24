<?php

namespace OroB2B\Bundle\ProductBundle\Tests\UnitProvider;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Entity\CategoryUnitPrecision;
use OroB2B\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
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
     * @var array
     */
    protected $systemPrecision = ['code'=>'kg', 'precision'=>3];

    /**
     * @var array
     */
    protected $categoryPrecision = ['code'=>'set', 'precision'=>2];

    public function setUp()
    {
        $configManager = $this
            ->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $map = [
            ['orob2b_product.default_unit', false, false, $this->systemPrecision['code']],
            ['orob2b_product.default_unit_precision', false, false, $this->systemPrecision['precision']]
        ];
        $configManager->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($map));

        $productUnitRepository = $this
            ->getMockBuilder('OroB2B\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $productUnit = new ProductUnit();
        $productUnit->setCode($this->systemPrecision['code']);

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

        $this->defaultProductUnitProvider = new DefaultProductUnitProvider($configManager, $managerRegistry);
    }

    /**
     * @dataProvider productUnitDataProvider
     * @param int $categoryId
     * @param array $expectedData
     */
    public function testGetDefaultProductUnit($categoryId, $expectedData)
    {
        if (null != $categoryId) {
            $this->defaultProductUnitProvider->setCategoryId($categoryId);
        }
        $expectedUnitPrecision = $this->createProductUnitPrecision(
            $expectedData['code'],
            $expectedData['precision']
        );
        $this->assertEquals(
            $expectedUnitPrecision,
            $this->defaultProductUnitProvider->getDefaultProductUnitPrecision()
        );
    }

    /**
     * @return  CategoryRepository
     *
     */
    private function createMockCategoryRepository()
    {
        $category1UnitPrecision = $this
            ->getMockBuilder('OroB2B\Bundle\CategoryBundle\Entity\Repository\CategoryUnitPrecision')
            ->setMethods(['getUnit', 'getPrecision'])
            ->getMock();
        
        $productUnit = new ProductUnit();
        $productUnit->setCode($this->categoryPrecision['code']);
        
        $category1UnitPrecision->expects($this->any())
            ->method('getUnit')
            ->will($this->returnValue($productUnit));
        
        $category1UnitPrecision->expects($this->any())
            ->method('getPrecision')
            ->will($this->returnValue($this->categoryPrecision['precision']));


        $category1 = $this->createMockCategory($category1UnitPrecision, null);
        $category2 = $this->createMockCategory(null, $category1);
        $category3 = $this->createMockCategory(null, null);


        $mapCategory = [
            [1, $category1],
            [2, $category2],
            [3, $category3],
        ];

        $categoryRepository = $this
            ->getMockBuilder('OroB2B\Bundle\CategoryBundle\Entity\Repository\CategoryRepository')
            ->setMethods(['findOneById'])
            ->getMock();

        $categoryRepository->expects($this->any())
            ->method('findOneById')
            ->will($this->returnValueMap($mapCategory));

        return $categoryRepository;
    }

    /**
     * @param CategoryUnitPrecision $categoryUnitPrecision,
     * @param Category $parent
     * @return  Category
     *
     */
    private function createMockCategory($categoryUnitPrecision, $parent)
    {
        $category =  $this
            ->getMockBuilder('OroB2B\Bundle\CategoryBundle\Entity\Repository\Category')
            ->setMethods(['getUnitPrecision', 'getParentCategory'])
            ->getMock();

        $category->expects($this->any())
            ->method('getUnitPrecision')
            ->willReturn($categoryUnitPrecision);

        $category->expects($this->any())
            ->method('getParentCategory')
            ->willReturn($parent);

        return $category;
    }

    /**
     * @param string $code
     * @param int $precision
     * @return ProductUnitPrecision
     */
    protected function createProductUnitPrecision($code, $precision)
    {
        $productUnit = new ProductUnit();
        $productUnit->setCode($code);
        $productUnitPrecision = new ProductUnitPrecision();
        return $productUnitPrecision->setUnit($productUnit)->setPrecision($precision);
    }

    /**
     * @return array
     */
    public function productUnitDataProvider()
    {
        return [
            'noCategory' => [
                'categoryId' => null,
                'expectedData' => $this->systemPrecision
            ],
            'CategoryWithPrecision' => [
                'categoryId' => 1,
                'expectedData' => $this->categoryPrecision
            ],
            'CategoryWithParentPrecision' => [
                'categoryId' => 2,
                'expectedData' => $this->categoryPrecision
            ],
            'CategoryWithNoPrecision' => [
                'categoryId' => 3,
                'expectedData' => $this->systemPrecision
            ],
        ];
    }
}
