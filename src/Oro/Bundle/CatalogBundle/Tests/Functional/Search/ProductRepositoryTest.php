<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\Search;

use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Search\ProductRepository;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadFrontendCategoryProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Query\Factory\WebsiteQueryFactory;
use Symfony\Component\HttpFoundation\Request;

class ProductRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->getContainer()->get('request_stack')->push(Request::create(''));

        $this->loadFixtures([
            LoadFrontendCategoryProductData::class,
        ]);
    }

    public function testGetCategoryCountsByCategoryWithCustomQuery()
    {
        $category = $this->getReference(LoadCategoryData::FIRST_LEVEL);
        $subCategory = $this->getReference(LoadCategoryData::SECOND_LEVEL1);

        $query = $this->getQueryFactory()->create();
        $query->addSelect('text.sku');
        $query->setFrom('oro_product_WEBSITE_ID');
        $query->addWhere(Criteria::expr()->startsWith('text.category_path', $subCategory->getMaterializedPath()));

        $this->assertEquals(
            [
                $subCategory->getId() => 3,
            ],
            $this->getRepository()->getCategoryCountsByCategory($category, $query)
        );
    }

    public function testGetCategoryCountsByCategory()
    {
        $category = $this->getReference(LoadCategoryData::FIRST_LEVEL);
        $category1 = $this->getReference(LoadCategoryData::SECOND_LEVEL1);
        $category2 = $this->getReference(LoadCategoryData::SECOND_LEVEL2);

        $this->assertEquals(
            [
                $category1->getId() => 3,
                $category2->getId() => 2,
            ],
            $this->getRepository()->getCategoryCountsByCategory($category)
        );
    }

    public function testGetCategoryCountsByCategoryWithoutProducts()
    {
        $category = $this->getReference(LoadCategoryData::FIRST_LEVEL);

        $query = $this->getQueryFactory()->create();
        $query->addSelect('text.sku');
        $query->setFrom('oro_product_WEBSITE_ID');
        $query->addWhere(Criteria::expr()->eq('text.category_path', 'null'));

        $this->assertEmpty($this->getRepository()->getCategoryCountsByCategory($category, $query));
    }

    public function testGetCategoriesCounts()
    {
        $categoriesIds = [
            $this->getCategoryId(LoadCategoryData::FIRST_LEVEL),
            $this->getCategoryId(LoadCategoryData::SECOND_LEVEL1),
            $this->getCategoryId(LoadCategoryData::SECOND_LEVEL2),
            $this->getCategoryId(LoadCategoryData::THIRD_LEVEL1),
            $this->getCategoryId(LoadCategoryData::THIRD_LEVEL2),
            $this->getCategoryId(LoadCategoryData::FOURTH_LEVEL1),
            $this->getCategoryId(LoadCategoryData::FOURTH_LEVEL2),
        ];

        $categories = $this->getContainer()->get('doctrine')->getManagerForClass(Category::class)
            ->getRepository(Category::class)
            ->findBy(['id' => $categoriesIds]);

        $result = $this->getRepository()->getCategoriesCounts($categories);

        $expectedResult = [
            $this->getCategoryId(LoadCategoryData::FIRST_LEVEL) => 6,
            $this->getCategoryId(LoadCategoryData::SECOND_LEVEL1) => 3,
            $this->getCategoryId(LoadCategoryData::SECOND_LEVEL2) => 2,
            $this->getCategoryId(LoadCategoryData::THIRD_LEVEL1) => 2,
            $this->getCategoryId(LoadCategoryData::THIRD_LEVEL2) => 2,
            $this->getCategoryId(LoadCategoryData::FOURTH_LEVEL1) => 1,
            $this->getCategoryId(LoadCategoryData::FOURTH_LEVEL2) => 2,
        ];

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return WebsiteQueryFactory
     */
    protected function getQueryFactory()
    {
        return $this->getContainer()->get('oro_website_search.query_factory');
    }

    /**
     * @return ProductRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('oro_catalog.website_search.repository.product');
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
