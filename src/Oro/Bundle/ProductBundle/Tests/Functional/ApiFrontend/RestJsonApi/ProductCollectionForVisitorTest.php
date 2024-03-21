<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCustomerData;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\ApiFrontend\RestJsonApi\WebCatalogTreeTestCase;
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
            '@OroProductBundle/Tests/Functional/ApiFrontend/DataFixtures/product_collection.yml'
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
