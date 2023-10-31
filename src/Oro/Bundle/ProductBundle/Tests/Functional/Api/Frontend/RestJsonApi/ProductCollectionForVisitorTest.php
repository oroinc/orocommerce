<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadCustomerData;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\Api\Frontend\RestJsonApi\WebCatalogTreeTestCase;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\WebsiteSearchExtensionTrait;

class ProductCollectionForVisitorTest extends WebCatalogTreeTestCase
{
    use WebsiteSearchExtensionTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->initializeVisitor();

        $this->loadFixtures([
            LoadCustomerData::class,
            '@OroProductBundle/Tests/Functional/Api/Frontend/DataFixtures/product_collection.yml'
        ]);
        $this->switchToWebCatalog();
    }

    protected function postFixtureLoad()
    {
        parent::postFixtureLoad();

        $this->reindexProductData();
    }

    public function testGet(): void
    {
        $response = $this->get(
            ['entity' => 'productcollection', 'id' => '<toString(@catalog1_node11_variant1->id)>']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'id'            => '<toString(@catalog1_node11_variant1->id)>',
                    'relationships' => [
                        'products' => [
                            'data' => [
                                ['type' => 'productsearch', 'id' => '<toString(@product4->id)>'],
                                ['type' => 'productsearch', 'id' => '<toString(@product3->id)>'],
                                ['type' => 'productsearch', 'id' => '<toString(@product1->id)>']
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
        self::assertArrayNotHasKey('included', self::jsonToArray($response->getContent()));
    }
}
