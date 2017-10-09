<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\Search;

use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\CatalogBundle\Search\ProductRepository;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadFrontendCategoryProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Query\Factory\WebsiteQueryFactory;
use Symfony\Component\HttpFoundation\Request;

class ProductRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->getContainer()->get('request_stack')->push(Request::create(''));

        $this->loadFixtures([
            LoadFrontendCategoryProductData::class,
        ]);
    }

    public function testGetCategoryCountsWithCustomQuery()
    {
        $category1 = $this->getReference(LoadCategoryData::THIRD_LEVEL1);
        $category2 = $this->getReference(LoadCategoryData::FOURTH_LEVEL1);

        $query = $this->getQueryFactory()->create();
        $query->addSelect('text.sku');
        $query->setFrom('oro_product_WEBSITE_ID');
        $query->addWhere(Criteria::expr()->startsWith('text.category_path', $category1->getMaterializedPath()));

        $this->assertEquals(
            [
                $category1->getMaterializedPath() => 1,
                $category2->getMaterializedPath() => 1,
            ],
            $this->getRepository()->getCategoryCounts($query)
        );
    }

    public function testGetCategoryCountsWithoutQuery()
    {
        $category1 = $this->getReference(LoadCategoryData::FIRST_LEVEL);
        $category2 = $this->getReference(LoadCategoryData::SECOND_LEVEL1);
        $category3 = $this->getReference(LoadCategoryData::THIRD_LEVEL1);
        $category4 = $this->getReference(LoadCategoryData::FOURTH_LEVEL1);
        $category5 = $this->getReference(LoadCategoryData::FOURTH_LEVEL2);

        $this->assertEquals(
            [
                $category1->getMaterializedPath() => 1,
                $category2->getMaterializedPath() => 1,
                $category3->getMaterializedPath() => 1,
                $category4->getMaterializedPath() => 1,
                $category5->getMaterializedPath() => 2,
            ],
            $this->getRepository()->getCategoryCounts()
        );
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
}
