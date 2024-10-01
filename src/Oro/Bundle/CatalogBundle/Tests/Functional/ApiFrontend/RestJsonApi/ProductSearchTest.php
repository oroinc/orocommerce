<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\WebsiteSearchExtensionTrait;

class ProductSearchTest extends FrontendRestJsonApiTestCase
{
    use WebsiteSearchExtensionTrait;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            '@OroCatalogBundle/Tests/Functional/ApiFrontend/DataFixtures/category.yml'
        ]);
    }

    #[\Override]
    protected function postFixtureLoad()
    {
        parent::postFixtureLoad();
        $this->reindexProductData();
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
