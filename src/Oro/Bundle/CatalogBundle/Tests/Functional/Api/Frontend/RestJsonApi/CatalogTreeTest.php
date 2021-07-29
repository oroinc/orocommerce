<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\Api\FrontendRestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class CatalogTreeTest extends FrontendRestJsonApiTestCase
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
            ['entity' => 'mastercatalogtree']
        );
        $this->assertResponseContains('cget_catalog_tree.yml', $response);
    }

    public function testGetListFilteredById()
    {
        $response = $this->cget(
            ['entity' => 'mastercatalogtree'],
            ['filter[id]' => '@category1->id']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'mastercatalogtree',
                        'id'         => '<toString(@category1->id)>',
                        'attributes' => [
                            'order' => 2
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListFilteredByCategory()
    {
        $response = $this->cget(
            ['entity' => 'mastercatalogtree'],
            ['filter[category]' => '@category1->id']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'mastercatalogtree',
                        'id'         => '<toString(@category1->id)>',
                        'attributes' => [
                            'order' => 2
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testTryToGetListFilteredByInvisibleCategory()
    {
        $response = $this->cget(
            ['entity' => 'mastercatalogtree'],
            ['filter[category]' => '@category3_1->id']
        );
        $this->assertResponseContains(
            ['data' => []],
            $response
        );
    }

    public function testGetListFilteredByParent()
    {
        $response = $this->cget(
            ['entity' => 'mastercatalogtree'],
            ['filter[parent]' => '@category1->id']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'mastercatalogtree',
                        'id'         => '<toString(@category1_1->id)>',
                        'attributes' => [
                            'order' => 3
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testTryToGetListFilteredByInvisibleParent()
    {
        $response = $this->cget(
            ['entity' => 'mastercatalogtree'],
            ['filter[parent]' => '@category3_1->id']
        );
        $this->assertResponseContains(
            ['data' => []],
            $response
        );
    }

    public function testGetListFilteredBySeveralParentsIncludingInvisibleParent()
    {
        $response = $this->cget(
            ['entity' => 'mastercatalogtree'],
            ['filter' => ['parent' => ['@category1->id', '@category3_1->id']]]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'mastercatalogtree',
                        'id'         => '<toString(@category1_1->id)>',
                        'attributes' => [
                            'order' => 3
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListFilteredByParentWithExistsOperator()
    {
        $response = $this->cget(
            ['entity' => 'mastercatalogtree'],
            ['filter[parent]' => ['exists' => true]]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'mastercatalogtree', 'id' => '<toString(@category1->id)>'],
                    ['type' => 'mastercatalogtree', 'id' => '<toString(@category1_1->id)>'],
                    ['type' => 'mastercatalogtree', 'id' => '<toString(@category1_1_1->id)>'],
                    ['type' => 'mastercatalogtree', 'id' => '<toString(@category2->id)>'],
                    ['type' => 'mastercatalogtree', 'id' => '<toString(@category3_1_1->id)>']
                ]
            ],
            $response
        );
    }

    public function testGetListFilteredByParentWithNotExistsOperator()
    {
        $response = $this->cget(
            ['entity' => 'mastercatalogtree', 'filter[parent]' => ['exists' => false]]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'mastercatalogtree', 'id' => '<toString(@root_category->id)>']
                ]
            ],
            $response
        );
    }

    public function testGetListFilteredByParentWithNeqOrNullOperator()
    {
        $response = $this->cget(
            ['entity' => 'mastercatalogtree'],
            ['filter[parent]' => ['neq_or_null' => '@category1->id']]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'mastercatalogtree', 'id' => '<toString(@root_category->id)>'],
                    ['type' => 'mastercatalogtree', 'id' => '<toString(@category1->id)>'],
                    ['type' => 'mastercatalogtree', 'id' => '<toString(@category1_1_1->id)>'],
                    ['type' => 'mastercatalogtree', 'id' => '<toString(@category2->id)>'],
                    ['type' => 'mastercatalogtree', 'id' => '<toString(@category3_1_1->id)>']
                ]
            ],
            $response
        );
    }

    public function testGetListFilteredByRoot()
    {
        $response = $this->cget(
            ['entity' => 'mastercatalogtree'],
            ['filter[root]' => ['gt' => '@category1->id']]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'mastercatalogtree',
                        'id'         => '<toString(@category1_1->id)>',
                        'attributes' => [
                            'order' => 3
                        ]
                    ],
                    [
                        'type'       => 'mastercatalogtree',
                        'id'         => '<toString(@category1_1_1->id)>',
                        'attributes' => [
                            'order' => 4
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListFilteredByRootWhenRootShouldBeReturned()
    {
        $response = $this->cget(
            ['entity' => 'mastercatalogtree'],
            ['filter[root]' => ['gte' => '@category1->id']]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'mastercatalogtree',
                        'id'         => '<toString(@category1->id)>',
                        'attributes' => [
                            'order' => 2
                        ]
                    ],
                    [
                        'type'       => 'mastercatalogtree',
                        'id'         => '<toString(@category1_1->id)>',
                        'attributes' => [
                            'order' => 3
                        ]
                    ],
                    [
                        'type'       => 'mastercatalogtree',
                        'id'         => '<toString(@category1_1_1->id)>',
                        'attributes' => [
                            'order' => 4
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testTryToGetListFilteredByInvisibleRoot()
    {
        $response = $this->cget(
            ['entity' => 'mastercatalogtree'],
            ['filter[root]' => ['gt' => '@category3_1->id']]
        );
        $this->assertResponseContains(
            ['data' => []],
            $response
        );
    }

    public function testGet()
    {
        $response = $this->get(
            ['entity' => 'mastercatalogtree', 'id' => '<toString(@category1_1->id)>']
        );
        $this->assertResponseContains('get_catalog_tree.yml', $response);
    }

    public function testGetWithIncludeAndFieldsFilters()
    {
        $response = $this->get(
            ['entity' => 'mastercatalogtree', 'id' => '<toString(@category1_1->id)>'],
            [
                'include'                   => 'parent,path',
                'fields[mastercatalogtree]' => 'order,parent,path'
            ]
        );
        $this->assertResponseContains('get_catalog_tree_include_fields.yml', $response);
    }

    public function testGetWithIncludeFilterForCategory()
    {
        $response = $this->get(
            ['entity' => 'mastercatalogtree', 'id' => '<toString(@category1_1->id)>'],
            ['include' => 'category']
        );
        $this->assertResponseContains('get_catalog_tree_include_category.yml', $response);
    }

    public function testTryToGetForInvisibleCategory()
    {
        $response = $this->get(
            ['entity' => 'mastercatalogtree', 'id' => '<toString(@category3_1->id)>'],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'access denied exception',
                'detail' => 'Access Denied.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToGetForNotExistingCategory()
    {
        $response = $this->get(
            ['entity' => 'mastercatalogtree', 'id' => '9999999'],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'not found http exception',
                'detail' => 'An entity with the requested identifier does not exist.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
        );
    }

    public function testTryToUpdate()
    {
        $response = $this->patch(
            ['entity' => 'mastercatalogtree', 'id' => '<toString(@category1_1->id)>'],
            [
                'data' => [
                    'type'       => 'mastercatalogtree',
                    'id'         => '<toString(@category1_1->id)>',
                    'attributes' => [
                        'order' => 100
                    ]
                ]
            ],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToCreate()
    {
        $response = $this->post(
            ['entity' => 'mastercatalogtree'],
            [
                'data' => [
                    'type'       => 'mastercatalogtree',
                    'attributes' => [
                        'order' => 100
                    ]
                ]
            ],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDelete()
    {
        $response = $this->delete(
            ['entity' => 'mastercatalogtree', 'id' => '<toString(@category1_1->id)>'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteList()
    {
        $response = $this->cdelete(
            ['entity' => 'mastercatalogtree'],
            ['filter' => ['id' => '<toString(@category1_1->id)>']],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testGetSubresourceForParent()
    {
        $response = $this->getSubresource(
            [
                'entity'      => 'mastercatalogtree',
                'id'          => '<toString(@category1_1->id)>',
                'association' => 'parent'
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'mastercatalogtree',
                    'id'         => '<toString(@category1->id)>',
                    'attributes' => [
                        'order' => 2
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetRelationshipForParent()
    {
        $response = $this->getRelationship(
            [
                'entity'      => 'mastercatalogtree',
                'id'          => '<toString(@category1_1->id)>',
                'association' => 'parent'
            ]
        );
        $this->assertResponseContains(
            [
                'data' => ['type' => 'mastercatalogtree', 'id' => '<toString(@category1->id)>']
            ],
            $response
        );
    }

    public function testTryToGetRelationshipForParentForInvisibleParent()
    {
        $response = $this->getRelationship(
            [
                'entity'      => 'mastercatalogtree',
                'id'          => '<toString(@category3_1->id)>',
                'association' => 'parent'
            ],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'access denied exception',
                'detail' => 'No access to the parent entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToGetRelationshipForParentForNotExistingParent()
    {
        $response = $this->getRelationship(
            [
                'entity'      => 'mastercatalogtree',
                'id'          => '9999999',
                'association' => 'parent'
            ],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'not found http exception',
                'detail' => 'The parent entity does not exist.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
        );
    }

    public function testGetSubresourceForPath()
    {
        $response = $this->getSubresource(
            [
                'entity'      => 'mastercatalogtree',
                'id'          => '<toString(@category1_1->id)>',
                'association' => 'path'
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'mastercatalogtree',
                        'id'         => '<toString(@root_category->id)>',
                        'attributes' => [
                            'order' => 1
                        ]
                    ],
                    [
                        'type'       => 'mastercatalogtree',
                        'id'         => '<toString(@category1->id)>',
                        'attributes' => [
                            'order' => 2
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetRelationshipForPath()
    {
        $response = $this->getRelationship(
            [
                'entity'      => 'mastercatalogtree',
                'id'          => '<toString(@category1_1->id)>',
                'association' => 'path'
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'mastercatalogtree', 'id' => '<toString(@root_category->id)>'],
                    ['type' => 'mastercatalogtree', 'id' => '<toString(@category1->id)>']
                ]
            ],
            $response
        );
    }

    public function testTryToGetRelationshipForPathForInvisibleParent()
    {
        $response = $this->getRelationship(
            [
                'entity'      => 'mastercatalogtree',
                'id'          => '<toString(@category3_1->id)>',
                'association' => 'path'
            ],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'access denied exception',
                'detail' => 'No access to the parent entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToGetRelationshipForPathForNotExistingParent()
    {
        $response = $this->getRelationship(
            [
                'entity'      => 'mastercatalogtree',
                'id'          => '9999999',
                'association' => 'path'
            ],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'not found http exception',
                'detail' => 'The parent entity does not exist.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
        );
    }

    public function testGetSubresourceForCategory()
    {
        $response = $this->getSubresource(
            [
                'entity'      => 'mastercatalogtree',
                'id'          => '<toString(@category1_1->id)>',
                'association' => 'category'
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'mastercatalogcategories',
                    'id'         => '<toString(@category1_1->id)>',
                    'attributes' => [
                        'title' => 'Category 1_1',
                        'url'   => '/category1_1_slug_default'
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetRelationshipForCategory()
    {
        $response = $this->getRelationship(
            [
                'entity'      => 'mastercatalogtree',
                'id'          => '<toString(@category1_1->id)>',
                'association' => 'category'
            ]
        );
        $this->assertResponseContains(
            [
                'data' => ['type' => 'mastercatalogcategories', 'id' => '<toString(@category1_1->id)>']
            ],
            $response
        );
    }

    public function testTryToGetRelationshipForCategoryForInvisibleParent()
    {
        $response = $this->getRelationship(
            [
                'entity'      => 'mastercatalogtree',
                'id'          => '<toString(@category3_1->id)>',
                'association' => 'category'
            ],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'access denied exception',
                'detail' => 'No access to the parent entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToGetRelationshipForCategoryForNotExistingParent()
    {
        $response = $this->getRelationship(
            [
                'entity'      => 'mastercatalogtree',
                'id'          => '9999999',
                'association' => 'category'
            ],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'not found http exception',
                'detail' => 'The parent entity does not exist.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
        );
    }
}
