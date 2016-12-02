<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Provider;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\CategoryDefaultProductOptions;
use Oro\Bundle\CatalogBundle\Model\CategoryUnitPrecision;
use Oro\Bundle\CatalogBundle\Provider\CategoryDefaultProductUnitProvider;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Service\SingleUnitModeService;

class CategoryDefaultProductUnitProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CategoryDefaultProductUnitProvider
     */
    protected $defaultProductUnitProvider;

    /**
     * @var array
     */
    protected $categoryPrecision = ['code' => 'set', 'precision' => 2];

    /**
     * @var array
     */
    protected $categories;

    /**
     * @var SingleUnitModeService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $singleUnitModeService;

    public function setUp()
    {
        $productUnit = new ProductUnit();
        $productUnit->setCode($this->categoryPrecision['code']);

        $category1UnitPrecision = new CategoryUnitPrecision();
        $category1UnitPrecision
            ->setUnit($productUnit)
            ->setPrecision($this->categoryPrecision['precision']);

        $category1 = $this->createCategory($category1UnitPrecision, null);
        $category2 = $this->createCategory(null, $category1);
        $category3 = $this->createCategory(null, null);

        $this->categories = [
            0 => null,
            1 => $category1,
            2 => $category2,
            3 => $category3,
        ];

        $this->singleUnitModeService = $this->getMockBuilder(SingleUnitModeService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->defaultProductUnitProvider = new CategoryDefaultProductUnitProvider($this->singleUnitModeService);
    }

    /**
     * @dataProvider productUnitDataProvider
     * @param boolean $singleUnitMode
     * @param int $categoryId
     * @param array $expectedData
     */
    public function testGetDefaultProductUnit($singleUnitMode, $categoryId, $expectedData)
    {
        $this->defaultProductUnitProvider->setCategory($this->categories[$categoryId]);
        $this->singleUnitModeService->expects(static::once())
            ->method('isSingleUnitMode')
            ->willReturn($singleUnitMode);

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
     * @param CategoryUnitPrecision $categoryUnitPrecision,
     * @param Category $parent
     * @return  Category
     */
    private function createCategory($categoryUnitPrecision, $parent)
    {
        $defaultProductOptions =  new CategoryDefaultProductOptions();
        $defaultProductOptions->setUnitPrecision($categoryUnitPrecision);
        
        $category =  new Category();
        $category->setDefaultProductOptions($defaultProductOptions);
        $category->setParentCategory($parent);

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
                'singleUnitMode' => false,
                'categoryId' => 0,
                'expectedData' => null
            ],
            'CategoryWithPrecision' => [
                'singleUnitMode' => false,
                'categoryId' => 1,
                'expectedData' => $this->categoryPrecision
            ],
            'CategoryWithParentPrecision' => [
                'singleUnitMode' => false,
                'categoryId' => 2,
                'expectedData' => $this->categoryPrecision
            ],
            'CategoryWithNoPrecision' => [
                'singleUnitMode' => false,
                'categoryId' => 3,
                'expectedData' => null
            ],
            'CategoryWithPrecisionButSingleUnitMode' => [
                'singleUnitMode' => true,
                'categoryId' => 1,
                'expectedData' => null
            ],
        ];
    }
}
