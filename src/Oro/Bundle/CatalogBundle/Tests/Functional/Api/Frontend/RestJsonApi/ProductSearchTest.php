<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\Api\FrontendRestJsonApiTestCase;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;

class ProductSearchTest extends FrontendRestJsonApiTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            '@OroCatalogBundle/Tests/Functional/Api/Frontend/DataFixtures/category.yml'
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

    public function testCategory()
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['filter' => ['searchQuery' => 'sku = PSKU1']]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'          => 'productsearch',
                        'id'            => '<toString(@product1->id)>',
                        'relationships' => [
                            'category' => [
                                'data' => [
                                    'type' => 'mastercatalogcategories',
                                    'id'   => '<toString(@category1->id)>'
                                ]
                            ]
                        ]
                    ]                ]
            ],
            $response
        );
    }

    public function testIncludeCategory()
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['filter' => ['searchQuery' => 'sku = PSKU1'], 'include' => 'category']
        );

        $this->assertResponseContains(
            [
                'data'     => [
                    [
                        'type'          => 'productsearch',
                        'id'            => '<toString(@product1->id)>',
                        'relationships' => [
                            'category' => [
                                'data' => [
                                    'type' => 'mastercatalogcategories',
                                    'id'   => '<toString(@product1->category->id)>'
                                ]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'mastercatalogcategories',
                        'id'   => '<toString(@product1->category->id)>'
                    ]
                ]
            ],
            $response
        );
    }
}
