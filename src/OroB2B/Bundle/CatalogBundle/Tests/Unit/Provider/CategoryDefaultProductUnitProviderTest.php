<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Unit\Provider;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use OroB2B\Bundle\CatalogBundle\Model\CategoryUnitPrecision;
use OroB2B\Bundle\CatalogBundle\Provider\CategoryDefaultProductUnitProvider;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

class CategoryDefaultProductUnitProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CategoryDefaultProductUnitProvider
     */
    protected $defaultProductUnitProvider;

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

        $manager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $mapRepository = [
            ['OroB2BCatalogBundle:Category', $this->createMockCategoryRepository()]
        ];
        $manager->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValueMap($mapRepository));

        $managerRegistry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $mapManager = [
            ['OroB2BCatalogBundle:Category', $manager]
        ];
        $managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->will($this->returnValueMap($mapManager));

        $this->defaultProductUnitProvider = new CategoryDefaultProductUnitProvider($configManager, $managerRegistry);
    }

    /**
     * @dataProvider productUnitDataProvider
     * @param int $categoryId
     * @param array $expectedData
     */
    public function testGetDefaultProductUnit($categoryId, $expectedData)
    {
        $this->defaultProductUnitProvider->setCategoryId($categoryId);

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
            ->getMockBuilder('OroB2B\Bundle\CategoryBundle\Model\CategoryUnitPrecision')
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
        $defaultProductOptions =  $this
            ->getMockBuilder('OroB2B\Bundle\CategoryBundle\Entity\CategoryDefaultProductOptions')
            ->setMethods(['getUnitPrecision'])
            ->getMock();

        $defaultProductOptions->expects($this->any())
            ->method('getUnitPrecision')
            ->willReturn($categoryUnitPrecision);
        
        $category =  $this
            ->getMockBuilder('OroB2B\Bundle\CategoryBundle\Entity\Category')
            ->setMethods(['getDefaultProductOptions', 'getParentCategory'])
            ->getMock();

        $category->expects($this->any())
            ->method('getDefaultProductOptions')
            ->willReturn($defaultProductOptions);

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
        if (!$code || !$precision) {
            return null;
        }
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
                'expectedData' => null
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
                'expectedData' => null
            ],
        ];
    }
}
