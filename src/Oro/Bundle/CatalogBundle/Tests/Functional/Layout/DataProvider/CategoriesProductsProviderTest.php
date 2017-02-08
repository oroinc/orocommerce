<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\Layout\DataProvider;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Layout\DataProvider\CategoriesProductsProvider;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CategoriesProductsProviderTest extends WebTestCase
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var CategoryRepository */
    protected $categoryRepository;

    /** @var ProductManager */
    protected $productManager;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures(
            [
                LoadCategoryData::class,
                LoadCategoryProductData::class,
            ]
        );

        $this->registry = $this->getContainer()->get('doctrine');
        $this->categoryRepository = $this->registry->getRepository('OroCatalogBundle:Category');
        $this->productManager = $this->getContainer()->get('oro_product.product.manager');
    }

    public function testGetCountByCategories()
    {
        $provider = new CategoriesProductsProvider($this->categoryRepository, $this->productManager);

        $categoryIds = [
            $this->getCategoryId(LoadCategoryData::FIRST_LEVEL),
            $this->getCategoryId(LoadCategoryData::SECOND_LEVEL1),
            $this->getCategoryId(LoadCategoryData::SECOND_LEVEL2),
            $this->getCategoryId(LoadCategoryData::THIRD_LEVEL1),
            $this->getCategoryId(LoadCategoryData::THIRD_LEVEL2),
            $this->getCategoryId(LoadCategoryData::FOURTH_LEVEL1),
            $this->getCategoryId(LoadCategoryData::FOURTH_LEVEL2),
        ];

        $result = $provider->getCountByCategories($categoryIds);

        $expectedResult = [
            $this->getCategoryId(LoadCategoryData::FIRST_LEVEL) => 7,
            $this->getCategoryId(LoadCategoryData::SECOND_LEVEL1) => 3,
            $this->getCategoryId(LoadCategoryData::SECOND_LEVEL2) => 3,
            $this->getCategoryId(LoadCategoryData::THIRD_LEVEL1) => 2,
            $this->getCategoryId(LoadCategoryData::THIRD_LEVEL2) => 3,
            $this->getCategoryId(LoadCategoryData::FOURTH_LEVEL1) => 1,
            $this->getCategoryId(LoadCategoryData::FOURTH_LEVEL2) => 2,
        ];

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @param string $reference
     *
     * @return int
     */
    private function getCategoryId($reference)
    {
        return $this->getReference($reference)->getId();
    }
}
