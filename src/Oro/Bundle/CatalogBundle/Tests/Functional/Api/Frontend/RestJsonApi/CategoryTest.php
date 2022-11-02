<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\Api\FrontendRestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class CategoryTest extends FrontendRestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            '@OroCatalogBundle/Tests/Functional/Api/Frontend/DataFixtures/category.yml'
        ]);
    }

    public function testGetList()
    {
        $response = $this->cget(
            ['entity' => 'mastercatalogcategories']
        );

        $this->assertResponseContains('cget_category.yml', $response);
    }

    public function testGetListFilterBySeveralIds()
    {
        $response = $this->cget(
            ['entity' => 'mastercatalogcategories'],
            ['filter' => ['id' => ['<toString(@category1->id)>', '<toString(@category2->id)>']]]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'mastercatalogcategories',
                        'id'         => '<toString(@category1->id)>',
                        'attributes' => [
                            'title' => 'Category 1'
                        ]
                    ],
                    [
                        'type'       => 'mastercatalogcategories',
                        'id'         => '<toString(@category2->id)>',
                        'attributes' => [
                            'title' => 'Category 2'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGet()
    {
        $response = $this->get(
            ['entity' => 'mastercatalogcategories', 'id' => '<toString(@category1->id)>']
        );

        $this->assertResponseContains('get_category.yml', $response);
    }

    public function testTryToGetInvisibleCategory()
    {
        $response = $this->get(
            ['entity' => 'mastercatalogcategories', 'id' => '<toString(@category3->id)>'],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'access denied exception',
                'detail' => 'No access to the entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToGetCategoryFromAnotherOrganization()
    {
        $category = $this->getEntityManager()
            ->getRepository(Category::class)
            ->findOneBy(['organization' => $this->getReference('another_organization')]);

        // guard
        self::assertNotNull($category);

        $response = $this->get(
            ['entity' => 'mastercatalogcategories', 'id' => (string)$category->getId()],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'access denied exception',
                'detail' => 'No access to the entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testGetUrlsForAnotherLocalization()
    {
        $response = $this->get(
            ['entity' => 'mastercatalogcategories', 'id' => '<toString(@category1->id)>'],
            ['fields[mastercatalogcategories]' => 'url,urls'],
            ['HTTP_X-Localization-ID' => $this->getReference('es')->getId()]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'mastercatalogcategories',
                    'id'         => '<toString(@category1->id)>',
                    'attributes' => [
                        'url'  => '/category1_slug_es',
                        'urls' => [
                            ['url' => '/category1_slug_default', 'localizationId' => '<toString(@en_US->id)>'],
                            ['url' => '/category1_slug_en_CA', 'localizationId' => '<toString(@en_CA->id)>']
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetOnlyCategoryPathRelationship()
    {
        $response = $this->get(
            ['entity' => 'mastercatalogcategories', 'id' => '<toString(@category1_1_1->id)>'],
            ['fields[mastercatalogcategories]' => 'categoryPath']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'mastercatalogcategories',
                    'id'            => '<toString(@category1_1_1->id)>',
                    'relationships' => [
                        'categoryPath' => [
                            'data' => [
                                ['type' => 'mastercatalogcategories', 'id' => '<toString(@root_category->id)>'],
                                ['type' => 'mastercatalogcategories', 'id' => '<toString(@category1->id)>'],
                                ['type' => 'mastercatalogcategories', 'id' => '<toString(@category1_1->id)>']
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );

        // test the order of categoryPath mastercatalogcategories
        $responseContent = self::jsonToArray($response->getContent());
        $categoryPathIds = [];
        foreach ($responseContent['data']['relationships']['categoryPath']['data'] as $item) {
            $categoryPathIds[] = (int)$item['id'];
        }
        $expectedCategoryPathIds = [
            $this->getReference('root_category')->getId(),
            $this->getReference('category1')->getId(),
            $this->getReference('category1_1')->getId()
        ];
        self::assertSame(
            $expectedCategoryPathIds,
            $categoryPathIds,
            'order of categoryPath mastercatalogcategories'
        );
    }

    public function testGetCategoryPathRelationshipWithIncludeFilter()
    {
        $response = $this->get(
            ['entity' => 'mastercatalogcategories', 'id' => '<toString(@category1_1_1->id)>'],
            ['fields[mastercatalogcategories]' => 'title,categoryPath', 'include' => 'categoryPath']
        );

        $this->assertResponseContains(
            [
                'data'     => [
                    'type'          => 'mastercatalogcategories',
                    'id'            => '<toString(@category1_1_1->id)>',
                    'attributes'    => [
                        'title' => 'Category 1_1_1'
                    ],
                    'relationships' => [
                        'categoryPath' => [
                            'data' => [
                                ['type' => 'mastercatalogcategories', 'id' => '<toString(@root_category->id)>'],
                                ['type' => 'mastercatalogcategories', 'id' => '<toString(@category1->id)>'],
                                ['type' => 'mastercatalogcategories', 'id' => '<toString(@category1_1->id)>']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'          => 'mastercatalogcategories',
                        'id'            => '<toString(@root_category->id)>',
                        'attributes'    => [
                            'title' => 'All Products'
                        ],
                        'relationships' => [
                            'categoryPath' => [
                                'data' => []
                            ]
                        ]
                    ],
                    [
                        'type'          => 'mastercatalogcategories',
                        'id'            => '<toString(@category1->id)>',
                        'attributes'    => [
                            'title' => 'Category 1'
                        ],
                        'relationships' => [
                            'categoryPath' => [
                                'data' => [
                                    ['type' => 'mastercatalogcategories', 'id' => '<toString(@root_category->id)>']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type'          => 'mastercatalogcategories',
                        'id'            => '<toString(@category1_1->id)>',
                        'attributes'    => [
                            'title' => 'Category 1_1'
                        ],
                        'relationships' => [
                            'categoryPath' => [
                                'data' => [
                                    ['type' => 'mastercatalogcategories', 'id' => '<toString(@root_category->id)>'],
                                    ['type' => 'mastercatalogcategories', 'id' => '<toString(@category1->id)>']
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testTryToUpdate()
    {
        $data = [
            'data' => [
                'type'       => 'mastercatalogcategories',
                'id'         => '<toString(@category1->id)>',
                'attributes' => [
                    'title' => 'Updated Category'
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'mastercatalogcategories', 'id' => '<toString(@category1->id)>'],
            $data,
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToCreate()
    {
        $data = [
            'data' => [
                'type'       => 'mastercatalogcategories',
                'attributes' => [
                    'title' => 'New Category'
                ]
            ]
        ];

        $response = $this->post(
            ['entity' => 'mastercatalogcategories'],
            $data,
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDelete()
    {
        $response = $this->delete(
            ['entity' => 'mastercatalogcategories', 'id' => '<toString(@category1->id)>'],
            [],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteList()
    {
        $response = $this->cdelete(
            ['entity' => 'mastercatalogcategories'],
            ['filter' => ['id' => '<toString(@category1->id)>']],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testGetSubresourceForProducts()
    {
        $response = $this->getSubresource(
            ['entity' => 'mastercatalogcategories', 'id' => '<toString(@category1->id)>', 'association' => 'products'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testGetRelationshipForProducts()
    {
        $response = $this->getRelationship(
            ['entity' => 'mastercatalogcategories', 'id' => '<toString(@category1->id)>', 'association' => 'products'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToUpdateRelationshipForProducts()
    {
        $response = $this->patchRelationship(
            ['entity' => 'mastercatalogcategories', 'id' => '<toString(@category1->id)>', 'association' => 'products'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToAddRelationshipForProducts()
    {
        $response = $this->postRelationship(
            ['entity' => 'mastercatalogcategories', 'id' => '<toString(@category1->id)>', 'association' => 'products'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToDeleteRelationshipForProducts()
    {
        $response = $this->deleteRelationship(
            ['entity' => 'mastercatalogcategories', 'id' => '<toString(@category1->id)>', 'association' => 'products'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testGetSubresourceForCategoryPath()
    {
        $response = $this->getSubresource(
            [
                'entity'      => 'mastercatalogcategories',
                'id'          => '<toString(@category1_1_1->id)>',
                'association' => 'categoryPath'
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'mastercatalogcategories',
                        'id'         => '<toString(@root_category->id)>',
                        'attributes' => [
                            'title' => 'All Products'
                        ]
                    ],
                    [
                        'type'       => 'mastercatalogcategories',
                        'id'         => '<toString(@category1->id)>',
                        'attributes' => [
                            'title' => 'Category 1'
                        ]
                    ],
                    [
                        'type'       => 'mastercatalogcategories',
                        'id'         => '<toString(@category1_1->id)>',
                        'attributes' => [
                            'title' => 'Category 1_1'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetRelationshipForCategoryPath()
    {
        $response = $this->getRelationship(
            [
                'entity'      => 'mastercatalogcategories',
                'id'          => '<toString(@category1_1_1->id)>',
                'association' => 'categoryPath'
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'mastercatalogcategories', 'id' => '<toString(@root_category->id)>'],
                    ['type' => 'mastercatalogcategories', 'id' => '<toString(@category1->id)>'],
                    ['type' => 'mastercatalogcategories', 'id' => '<toString(@category1_1->id)>']
                ]
            ],
            $response
        );
    }

    public function testGetRelationshipForCategoryPathForRootCategory()
    {
        $response = $this->getRelationship(
            [
                'entity'      => 'mastercatalogcategories',
                'id'          => '<toString(@root_category->id)>',
                'association' => 'categoryPath'
            ]
        );
        $this->assertResponseContains(['data' => []], $response);
    }
}
