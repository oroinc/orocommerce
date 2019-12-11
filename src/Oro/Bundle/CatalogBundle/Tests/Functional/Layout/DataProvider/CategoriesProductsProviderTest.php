<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\Layout\DataProvider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Layout\DataProvider\CategoriesProductsProvider;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Search\ProductRepository;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures\LoadProductsToIndex;
use Symfony\Component\HttpFoundation\Request;

class CategoriesProductsProviderTest extends FrontendWebTestCase
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var CategoryRepository */
    protected $categoryRepository;

    /** @var ProductRepository */
    protected $searchRepository;

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
                LoadWebsiteData::class,
                LoadProductsToIndex::class,
            ]
        );

        $this->getContainer()->get('oro_website_search.indexer')
            ->reindex(Product::class);

        $this->getContainer()->get('request_stack')->push(Request::create(''));
        $this->setCurrentWebsite('default');

        $this->registry = $this->getContainer()->get('doctrine');
        $this->categoryRepository = $this->registry->getRepository('OroCatalogBundle:Category');
        $this->searchRepository = $this->getContainer()->get('oro_catalog.website_search.repository.product');
    }

    public function testGetCountByCategories()
    {
        $provider = new CategoriesProductsProvider(
            $this->registry,
            $this->searchRepository
        );

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
     * @param string $reference
     *
     * @return int
     */
    private function getCategoryId($reference)
    {
        return $this->getReference($reference)->getId();
    }
}
