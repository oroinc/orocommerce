<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\Api\FrontendRestJsonApiTestCase;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * This test is in the own class to avoid using dbIsolationPerTest in ProductSearchTest (to speed uo tests).
 */
class ProductSearchFilterByIntFieldWithStringValueTest extends FrontendRestJsonApiTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            '@OroProductBundle/Tests/Functional/Api/Frontend/DataFixtures/product.yml',
            '@OroProductBundle/Tests/Functional/Api/Frontend/DataFixtures/product_prices.yml',
        ]);
    }

    protected function postFixtureLoad()
    {
        parent::postFixtureLoad();
        $this->getSearchIndexer()->reindex(Product::class);
    }

    /**
     * @return IndexerInterface
     */
    private function getSearchIndexer()
    {
        return self::getContainer()->get('oro_website_search.indexer');
    }

    public function testTryToFilterByIntFieldWithStringValue()
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['filter' => ['searchQuery' => 'id = "test test"']],
            [],
            false
        );

        if ($response->getStatusCode() === Response::HTTP_OK) {
            // ORM search engine - MySql
            self::assertSame(['data' => []], self::jsonToArray($response->getContent()));
        } else {
            // Elastic search engine or ORM search engine - PostgreSql
            $this->assertResponseValidationError(
                [
                    'title'  => 'filter constraint',
                    'detail' => 'Invalid search query.',
                    'source' => [
                        'parameter' => 'filter[searchQuery]'
                    ]
                ],
                $response
            );
        }
    }
}
