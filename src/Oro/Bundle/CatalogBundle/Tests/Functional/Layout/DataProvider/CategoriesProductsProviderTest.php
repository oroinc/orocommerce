<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\Layout\DataProvider;

use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures\LoadProductsToIndex;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\WebsiteSearchExtensionTrait;
use Symfony\Component\HttpFoundation\Request;

class CategoriesProductsProviderTest extends FrontendWebTestCase
{
    use WebsiteSearchExtensionTrait;

    protected function setUp(): void
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

        self::reindex(Product::class);

        self::getContainer()->get('request_stack')->push(Request::create(''));
        $this->setCurrentWebsite('default');
    }

    public function testGetCountByCategories(): void
    {
        $categoryIds = [
            $this->getCategoryId(LoadCategoryData::FIRST_LEVEL),
            $this->getCategoryId(LoadCategoryData::SECOND_LEVEL1),
            $this->getCategoryId(LoadCategoryData::SECOND_LEVEL2),
            $this->getCategoryId(LoadCategoryData::THIRD_LEVEL1),
            $this->getCategoryId(LoadCategoryData::THIRD_LEVEL2),
            $this->getCategoryId(LoadCategoryData::FOURTH_LEVEL1),
            $this->getCategoryId(LoadCategoryData::FOURTH_LEVEL2),
        ];

        self::getContainer()->get('oro_catalog.layout.data_provider.category.cache')->clear();
        $provider = self::getContainer()->get('oro_catalog.layout.data_provider.featured_categories_products');
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

        self::assertEquals($expectedResult, $result);
    }

    /**
     * @param string $reference
     *
     * @return int
     */
    private function getCategoryId($reference): int
    {
        return $this->getReference($reference)->getId();
    }
}
