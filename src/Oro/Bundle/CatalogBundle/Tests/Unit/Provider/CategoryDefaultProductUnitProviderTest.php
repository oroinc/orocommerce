<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Provider;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\CategoryDefaultProductOptions;
use Oro\Bundle\CatalogBundle\Model\CategoryUnitPrecision;
use Oro\Bundle\CatalogBundle\Provider\CategoryDefaultProductUnitProvider;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;

class CategoryDefaultProductUnitProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var CategoryDefaultProductUnitProvider */
    private $defaultProductUnitProvider;

    /** @var array */
    private $categoryPrecision = ['code' => 'set', 'precision' => 2];

    /** @var array */
    private $categories;

    protected function setUp(): void
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

        $this->defaultProductUnitProvider = new CategoryDefaultProductUnitProvider();
    }

    /**
     * @dataProvider productUnitDataProvider
     * @param int $categoryId
     * @param array $expectedData
     */
    public function testGetDefaultProductUnit($categoryId, array $expectedData)
    {
        $this->defaultProductUnitProvider->setCategory($this->categories[$categoryId]);

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
     * @param CategoryUnitPrecision $categoryUnitPrecision
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
    private function createProductUnitPrecision($code, $precision)
    {
        if (!$code || !$precision) {
            return null;
        }
        $productUnit = new ProductUnit();
        $productUnit->setCode($code);
        $productUnitPrecision = new ProductUnitPrecision();
        return $productUnitPrecision->setUnit($productUnit)->setPrecision($precision);
    }

    public function productUnitDataProvider(): array
    {
        return [
            'noCategory' => [
                'categoryId' => 0,
                'expectedData' => ['code' => null, 'precision' => null]
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
                'expectedData' => ['code' => null, 'precision' => null]
            ],
        ];
    }
}
