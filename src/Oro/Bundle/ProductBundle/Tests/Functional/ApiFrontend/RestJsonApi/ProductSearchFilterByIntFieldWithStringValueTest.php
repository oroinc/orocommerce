<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\WebsiteSearchExtensionTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * This test is in the own class to avoid using dbIsolationPerTest in ProductSearchTest (to speed uo tests).
 */
class ProductSearchFilterByIntFieldWithStringValueTest extends FrontendRestJsonApiTestCase
{
    use WebsiteSearchExtensionTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            '@OroProductBundle/Tests/Functional/ApiFrontend/DataFixtures/product.yml',
            '@OroProductBundle/Tests/Functional/ApiFrontend/DataFixtures/product_prices.yml',
        ]);
    }

    protected function postFixtureLoad()
    {
        parent::postFixtureLoad();
        $this->reindexProductData();
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
