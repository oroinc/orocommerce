<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CatalogTreeForVisitorTest extends FrontendRestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->initializeVisitor();
        $this->loadFixtures([
            '@OroCatalogBundle/Tests/Functional/ApiFrontend/DataFixtures/category.yml'
        ]);
    }

    public function testGetList()
    {
        $response = $this->cget(
            ['entity' => 'mastercatalogtree']
        );
        $this->assertResponseContains('cget_catalog_tree.yml', $response);
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
