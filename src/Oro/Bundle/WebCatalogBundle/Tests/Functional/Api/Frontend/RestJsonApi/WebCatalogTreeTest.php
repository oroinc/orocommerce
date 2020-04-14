<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\WebsiteSearchExtensionTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @dbIsolationPerTest
 */
class WebCatalogTreeTest extends WebCatalogTreeTestCase
{
    use WebsiteSearchExtensionTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            '@OroWebCatalogBundle/Tests/Functional/Api/Frontend/DataFixtures/content_node.yml'
        ]);
        $this->switchToWebCatalog();
    }

    protected function postFixtureLoad()
    {
        parent::postFixtureLoad();

        $this->reindexProductData();
    }

    public function testGetList()
    {
        $response = $this->cget(
            ['entity' => 'webcatalogtree']
        );
        $this->assertResponseContains('cget_content_node.yml', $response);
    }

    public function testGetListWithIncludeAndFieldsFilters()
    {
        $response = $this->cget(
            ['entity' => 'webcatalogtree'],
            [
                'filter[id]'             => '<toString(@catalog1_node11->id)>',
                'include'                => 'parent,path',
                'fields[webcatalogtree]' => 'title,parent,path'
            ]
        );
        $this->assertResponseContains('cget_content_node_include_fields.yml', $response);
    }

    public function testGetListForAnotherLocalization()
    {
        $response = $this->cget(
            ['entity' => 'webcatalogtree'],
            [],
            ['HTTP_X-Localization-ID' => $this->getReference('es')->getId()]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_rootNode->id)>'],
                    ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_node1->id)>'],
                    ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_node11->id)>'],
                    ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_node111->id)>'],
                    ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_node12->id)>'],
                    ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_node13_es->id)>'],
                    ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_node131->id)>'],
                    ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_node2->id)>']
                ]
            ],
            $response
        );
    }

    public function testGetListForAnotherCustomer()
    {
        $response = $this->cget(
            ['entity' => 'webcatalogtree'],
            [],
            self::generateWsseAuthHeader('user1@example.com', 'user1')
        );
        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_rootNode->id)>'],
                    ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_node1->id)>'],
                    ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_node11->id)>'],
                    ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_node111->id)>'],
                    ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_node12->id)>'],
                    ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_node14_customer1->id)>'],
                    ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_node141->id)>'],
                    ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_node15_customer_group1->id)>'],
                    ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_node151->id)>'],
                    ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_node2->id)>']
                ]
            ],
            $response
        );
    }

    public function testGetListForAnotherLocalizationAndCustomer()
    {
        $response = $this->cget(
            ['entity' => 'webcatalogtree'],
            [],
            array_merge(
                ['HTTP_X-Localization-ID' => $this->getReference('es')->getId()],
                self::generateWsseAuthHeader('user1@example.com', 'user1')
            )
        );
        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_rootNode->id)>'],
                    ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_node1->id)>'],
                    ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_node11->id)>'],
                    ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_node111->id)>'],
                    ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_node12->id)>'],
                    ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_node13_es->id)>'],
                    ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_node131->id)>'],
                    ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_node1311_customer1->id)>'],
                    ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_node13111->id)>'],
                    ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_node14_customer1->id)>'],
                    ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_node141->id)>'],
                    ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_node15_customer_group1->id)>'],
                    ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_node151->id)>'],
                    ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_node2->id)>']
                ]
            ],
            $response
        );
    }

    public function testGet()
    {
        $response = $this->get(
            ['entity' => 'webcatalogtree', 'id' => '<toString(@catalog1_node11->id)>']
        );
        $this->assertResponseContains('get_content_node.yml', $response);
    }

    public function testGetWithIncludeAndFieldsFilters()
    {
        $response = $this->get(
            ['entity' => 'webcatalogtree', 'id' => '<toString(@catalog1_node11->id)>'],
            [
                'include'                => 'parent,path',
                'fields[webcatalogtree]' => 'title,parent,path'
            ]
        );
        $this->assertResponseContains('get_content_node_include_fields.yml', $response);
    }

    public function testGetListFilteredByParent()
    {
        $response = $this->cget(
            ['entity' => 'webcatalogtree'],
            ['filter' => ['parent' => '@catalog1_node1->id']]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_node11->id)>'],
                    ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_node12->id)>']
                ]
            ],
            $response
        );
    }

    public function testGetListFilteredByRootFilter()
    {
        $response = $this->cget(
            ['entity' => 'webcatalogtree'],
            ['filter' => ['root' => ['gt' => '@catalog1_node1->id']]]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_node11->id)>'],
                    ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_node111->id)>'],
                    ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_node12->id)>']
                ]
            ],
            $response
        );
    }

    public function testGetListFilteredByRootFilterIncludingSpecifiedRootNode()
    {
        $response = $this->cget(
            ['entity' => 'webcatalogtree'],
            ['filter' => ['root' => ['gte' => '@catalog1_node1->id']]]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_node1->id)>'],
                    ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_node11->id)>'],
                    ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_node111->id)>'],
                    ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_node12->id)>']
                ]
            ],
            $response
        );
    }

    public function testGetListFilteredByLevel()
    {
        $response = $this->cget(
            ['entity' => 'webcatalogtree'],
            ['filter' => ['level' => ['lte' => 2]]]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_rootNode->id)>'],
                    ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_node1->id)>'],
                    ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_node11->id)>'],
                    ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_node12->id)>'],
                    ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_node2->id)>']
                ]
            ],
            $response
        );
    }

    public function testTryToGetWhenWebCatalogIsNotEnabled()
    {
        $this->switchToMasterCatalog();
        $response = $this->get(
            ['entity' => 'webcatalogtree', 'id' => '<toString(@catalog1_node11->id)>'],
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

    public function testTryToGetNodeFromAnotherWebCatalog()
    {
        $response = $this->get(
            ['entity' => 'webcatalogtree', 'id' => '<toString(@catalog2_node1->id)>'],
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

    public function testGetSharedNodeFromAnotherLocalization()
    {
        $response = $this->get(
            ['entity' => 'webcatalogtree', 'id' => '<toString(@catalog1_node11->id)>'],
            [],
            ['HTTP_X-Localization-ID' => $this->getReference('es')->getId()]
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_node11->id)>']],
            $response
        );
    }

    public function testGetLocalizationSpecificNodeFromOwnLocalization()
    {
        $response = $this->get(
            ['entity' => 'webcatalogtree', 'id' => '<toString(@catalog1_node13_es->id)>'],
            [],
            ['HTTP_X-Localization-ID' => $this->getReference('es')->getId()]
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_node13_es->id)>']],
            $response
        );
    }

    public function testGetNestedNodeForLocalizationSpecificNodeFromOwnLocalization()
    {
        $response = $this->get(
            ['entity' => 'webcatalogtree', 'id' => '<toString(@catalog1_node131->id)>'],
            [],
            ['HTTP_X-Localization-ID' => $this->getReference('es')->getId()]
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_node131->id)>']],
            $response
        );
    }

    public function testTryToGetLocalizationSpecificNodeFromAnotherLocalization()
    {
        $response = $this->get(
            ['entity' => 'webcatalogtree', 'id' => '<toString(@catalog1_node13_es->id)>'],
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

    public function testTryToGetNestedNodeForLocalizationSpecificNodeFromAnotherLocalization()
    {
        $response = $this->get(
            ['entity' => 'webcatalogtree', 'id' => '<toString(@catalog1_node131->id)>'],
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

    public function testGetCustomerSpecificNodeFromOwnCustomer()
    {
        $response = $this->get(
            ['entity' => 'webcatalogtree', 'id' => '<toString(@catalog1_node14_customer1->id)>'],
            [],
            self::generateWsseAuthHeader('user1@example.com', 'user1')
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_node14_customer1->id)>']],
            $response
        );
    }

    public function testGetNestedNodeForCustomerSpecificNodeFromOwnCustomer()
    {
        $response = $this->get(
            ['entity' => 'webcatalogtree', 'id' => '<toString(@catalog1_node141->id)>'],
            [],
            self::generateWsseAuthHeader('user1@example.com', 'user1')
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_node141->id)>']],
            $response
        );
    }

    public function testTryToGetCustomerSpecificNodeFromAnotherCustomer()
    {
        $response = $this->get(
            ['entity' => 'webcatalogtree', 'id' => '<toString(@catalog1_node14_customer1->id)>'],
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

    public function testTryToGetNestedNodeForCustomerSpecificNodeFromAnotherCustomer()
    {
        $response = $this->get(
            ['entity' => 'webcatalogtree', 'id' => '<toString(@catalog1_node141->id)>'],
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

    public function testGetCustomerGroupSpecificNodeFromOwnCustomerGroup()
    {
        $response = $this->get(
            ['entity' => 'webcatalogtree', 'id' => '<toString(@catalog1_node15_customer_group1->id)>'],
            [],
            self::generateWsseAuthHeader('user1@example.com', 'user1')
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_node15_customer_group1->id)>']],
            $response
        );
    }

    public function testGetNestedNodeForCustomerGroupSpecificNodeFromOwnCustomerGroup()
    {
        $response = $this->get(
            ['entity' => 'webcatalogtree', 'id' => '<toString(@catalog1_node151->id)>'],
            [],
            self::generateWsseAuthHeader('user1@example.com', 'user1')
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_node151->id)>']],
            $response
        );
    }

    public function testTryToGetCustomerGroupSpecificNodeFromAnotherCustomerGroup()
    {
        $response = $this->get(
            ['entity' => 'webcatalogtree', 'id' => '<toString(@catalog1_node15_customer_group1->id)>'],
            [],
            self::generateWsseAuthHeader('user2@example.com', 'user2'),
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

    public function testTryToGetNestedNodeForCustomerGroupSpecificNodeFromAnotherCustomerGroup()
    {
        $response = $this->get(
            ['entity' => 'webcatalogtree', 'id' => '<toString(@catalog1_node151->id)>'],
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

    public function testTryToGetCustomerGroupSpecificNodeFromCustomerWithoutCustomerGroup()
    {
        $response = $this->get(
            ['entity' => 'webcatalogtree', 'id' => '<toString(@catalog1_node15_customer_group1->id)>'],
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

    public function testGetScopedNodeWithSeveralScopedParents()
    {
        $response = $this->get(
            ['entity' => 'webcatalogtree', 'id' => '<toString(@catalog1_node1311_customer1->id)>'],
            [],
            array_merge(
                ['HTTP_X-Localization-ID' => $this->getReference('es')->getId()],
                self::generateWsseAuthHeader('user1@example.com', 'user1')
            )
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_node1311_customer1->id)>']],
            $response
        );
    }

    public function testTryToGetScopedNodeWithSeveralScopedParentsForAnotherLocalization()
    {
        $response = $this->get(
            ['entity' => 'webcatalogtree', 'id' => '<toString(@catalog1_node1311_customer1->id)>'],
            [],
            self::generateWsseAuthHeader('user1@example.com', 'user1'),
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

    public function testTryToGetScopedNodeWithSeveralScopedParentsForAnotherCustomer()
    {
        $response = $this->get(
            ['entity' => 'webcatalogtree', 'id' => '<toString(@catalog1_node1311_customer1->id)>'],
            [],
            ['HTTP_X-Localization-ID' => $this->getReference('es')->getId()],
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

    public function testTryToGetScopedNodeWithSeveralScopedParentsForAnotherLocalizationAndCustomer()
    {
        $response = $this->get(
            ['entity' => 'webcatalogtree', 'id' => '<toString(@catalog1_node1311_customer1->id)>'],
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

    public function testGetNestedNodeWithSeveralScopedParents()
    {
        $response = $this->get(
            ['entity' => 'webcatalogtree', 'id' => '<toString(@catalog1_node13111->id)>'],
            [],
            array_merge(
                ['HTTP_X-Localization-ID' => $this->getReference('es')->getId()],
                self::generateWsseAuthHeader('user1@example.com', 'user1')
            )
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_node13111->id)>']],
            $response
        );
    }

    public function testTryToGetNestedNodeWithSeveralScopedParentsForAnotherLocalization()
    {
        $response = $this->get(
            ['entity' => 'webcatalogtree', 'id' => '<toString(@catalog1_node13111->id)>'],
            [],
            self::generateWsseAuthHeader('user1@example.com', 'user1'),
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

    public function testTryToGetNestedNodeWithSeveralScopedParentsForAnotherCustomer()
    {
        $response = $this->get(
            ['entity' => 'webcatalogtree', 'id' => '<toString(@catalog1_node13111->id)>'],
            [],
            ['HTTP_X-Localization-ID' => $this->getReference('es')->getId()],
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

    public function testTryToGetNestedNodeWithSeveralScopedParentsForAnotherLocalizationAndCustomer()
    {
        $response = $this->get(
            ['entity' => 'webcatalogtree', 'id' => '<toString(@catalog1_node13111->id)>'],
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

    public function testGetUrlsForAnotherLocalization()
    {
        $currentLocalizationId = $this->getCurrentLocalizationId();
        $response = $this->get(
            ['entity' => 'webcatalogtree', 'id' => '<toString(@catalog1_node11->id)>'],
            ['fields[webcatalogtree]' => 'url,urls'],
            ['HTTP_X-Localization-ID' => $this->getReference('es')->getId()]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'webcatalogtree',
                    'id'         => '<toString(@catalog1_node11->id)>',
                    'attributes' => [
                        'url'  => '/catalog1_node11_es',
                        'urls' => [
                            ['url' => '/catalog1_node11', 'localizationId' => (string)$currentLocalizationId],
                            ['url' => '/catalog1_node11_en_CA', 'localizationId' => '<toString(@en_CA->id)>']
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testTryToUpdate()
    {
        $response = $this->patch(
            ['entity' => 'webcatalogtree', 'id' => '<toString(@catalog1_node11->id)>'],
            [
                'data' => [
                    'type'       => 'webcatalogtree',
                    'id'         => '<toString(@catalog1_node11->id)>',
                    'attributes' => [
                        'title' => 'Updated Node'
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
            ['entity' => 'webcatalogtree'],
            [
                'data' => [
                    'type'       => 'webcatalogtree',
                    'attributes' => [
                        'title' => 'New Node'
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
            ['entity' => 'webcatalogtree', 'id' => '<toString(@catalog1_node11->id)>'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteList()
    {
        $response = $this->cdelete(
            ['entity' => 'webcatalogtree'],
            ['filter' => ['id' => '<toString(@catalog1_node11->id)>']],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testGetSubresourceForParent()
    {
        $response = $this->getSubresource(
            [
                'entity'      => 'webcatalogtree',
                'id'          => '<toString(@catalog1_node111->id)>',
                'association' => 'parent'
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'webcatalogtree',
                    'id'         => '<toString(@catalog1_node11->id)>',
                    'attributes' => [
                        'title' => 'Web Catalog 1 Node 1.1'
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
                'entity'      => 'webcatalogtree',
                'id'          => '<toString(@catalog1_node111->id)>',
                'association' => 'parent'
            ]
        );
        $this->assertResponseContains(
            [
                'data' => ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_node11->id)>']
            ],
            $response
        );
    }

    public function testGetSubresourceForParentWhenParentNodeIsNotAccessible()
    {
        $response = $this->getSubresource(
            [
                'entity'      => 'webcatalogtree',
                'id'          => '<toString(@catalog1_node13_es->id)>',
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

    public function testGetRelationshipForParentWhenParentNodeIsNotAccessible()
    {
        $response = $this->getRelationship(
            [
                'entity'      => 'webcatalogtree',
                'id'          => '<toString(@catalog1_node13_es->id)>',
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

    public function testGetSubresourceForParentWhenParentNodeDoesNotExist()
    {
        $response = $this->getSubresource(
            [
                'entity'      => 'webcatalogtree',
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

    public function testGetRelationshipForParentWhenParentNodeDoesNotExist()
    {
        $response = $this->getRelationship(
            [
                'entity'      => 'webcatalogtree',
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
                'entity'      => 'webcatalogtree',
                'id'          => '<toString(@catalog1_node111->id)>',
                'association' => 'path'
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'webcatalogtree',
                        'id'         => '<toString(@catalog1_rootNode->id)>',
                        'attributes' => [
                            'title' => 'Web Catalog 1 Root Node'
                        ]
                    ],
                    [
                        'type'       => 'webcatalogtree',
                        'id'         => '<toString(@catalog1_node1->id)>',
                        'attributes' => [
                            'title' => 'Web Catalog 1 Node 1'
                        ]
                    ],
                    [
                        'type'       => 'webcatalogtree',
                        'id'         => '<toString(@catalog1_node11->id)>',
                        'attributes' => [
                            'title' => 'Web Catalog 1 Node 1.1'
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
                'entity'      => 'webcatalogtree',
                'id'          => '<toString(@catalog1_node111->id)>',
                'association' => 'path'
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_rootNode->id)>'],
                    ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_node1->id)>'],
                    ['type' => 'webcatalogtree', 'id' => '<toString(@catalog1_node11->id)>']
                ]
            ],
            $response
        );
    }

    public function testGetSubresourceForPathWhenParentNodeIsNotAccessible()
    {
        $response = $this->getSubresource(
            [
                'entity'      => 'webcatalogtree',
                'id'          => '<toString(@catalog1_node13_es->id)>',
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

    public function testGetRelationshipForPathWhenParentNodeIsNotAccessible()
    {
        $response = $this->getRelationship(
            [
                'entity'      => 'webcatalogtree',
                'id'          => '<toString(@catalog1_node13_es->id)>',
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

    public function testGetSubresourceForPathWhenParentNodeDoesNotExist()
    {
        $response = $this->getSubresource(
            [
                'entity'      => 'webcatalogtree',
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

    public function testGetRelationshipForPathWhenParentNodeDoesNotExist()
    {
        $response = $this->getRelationship(
            [
                'entity'      => 'webcatalogtree',
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

    public function testGetWithOnlyContentFieldFilter()
    {
        $response = $this->get(
            ['entity' => 'webcatalogtree', 'id' => '<toString(@catalog1_node11->id)>'],
            ['fields[webcatalogtree]' => 'content']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'webcatalogtree',
                    'id'            => '<toString(@catalog1_node11->id)>',
                    'relationships' => [
                        'content' => [
                            'data' => ['type' => 'mastercatalogcategories', 'id' => '<toString(@category1->id)>']
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetContentWithOnlyVariantFieldFilterWithSeveralScopedParentsForAnotherCustomer()
    {
        $response = $this->get(
            ['entity' => 'webcatalogtree', 'id' => '<toString(@catalog1_node11->id)>'],
            ['fields[webcatalogtree]' => 'content'],
            self::generateWsseAuthHeader('user1@example.com', 'user1')
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'webcatalogtree',
                    'id'            => '<toString(@catalog1_node11->id)>',
                    'relationships' => [
                        'content' => [
                            'data' => ['type' => 'mastercatalogcategories', 'id' => '<toString(@category11->id)>']
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetContentWithOnlyVariantFieldFilterWithSeveralScopedParentsForAnotherLocalization()
    {
        $response = $this->get(
            ['entity' => 'webcatalogtree', 'id' => '<toString(@catalog1_node11->id)>'],
            ['fields[webcatalogtree]' => 'content'],
            ['HTTP_X-Localization-ID' => $this->getReference('es')->getId()]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'webcatalogtree',
                    'id'            => '<toString(@catalog1_node11->id)>',
                    'relationships' => [
                        'content' => [
                            'data' => ['type' => 'products', 'id' => '<toString(@product2->id)>']
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetContentWithOnlyVariantFieldFilterWithSeveralScopedParents()
    {
        $response = $this->get(
            ['entity' => 'webcatalogtree', 'id' => '<toString(@catalog1_node11->id)>'],
            ['fields[webcatalogtree]' => 'content'],
            array_merge(
                ['HTTP_X-Localization-ID' => $this->getReference('es')->getId()],
                self::generateWsseAuthHeader('user1@example.com', 'user1')
            )
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'webcatalogtree',
                    'id'            => '<toString(@catalog1_node11->id)>',
                    'relationships' => [
                        'content' => [
                            'data' => ['type' => 'mastercatalogcategories', 'id' => '<toString(@category11->id)>']
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetWithIncludeForSystemPageContentVariant()
    {
        $response = $this->get(
            ['entity' => 'webcatalogtree', 'id' => '<toString(@catalog1_node111->id)>'],
            [
                'fields[webcatalogtree]' => 'content',
                'include'                => 'content'
            ]
        );
        $this->assertResponseContains('get_content_node_include_system_page.yml', $response);
    }

    public function testGetWithIncludeForCategoryContentVariant()
    {
        $response = $this->get(
            ['entity' => 'webcatalogtree', 'id' => '<toString(@catalog1_node11->id)>'],
            [
                'fields[webcatalogtree]' => 'content',
                'include'                => 'content'
            ]
        );
        $this->assertResponseContains('get_content_node_include_category.yml', $response);
    }

    public function testGetWithIncludeForProductContentVariant()
    {
        $this->getReferenceRepository()->setReference('current_localization', $this->getCurrentLocalization());
        $response = $this->get(
            ['entity' => 'webcatalogtree', 'id' => '<toString(@catalog1_node11->id)>'],
            [
                'fields[webcatalogtree]' => 'content',
                'include'                => 'content'
            ],
            ['HTTP_X-Localization-ID' => $this->getReference('es')->getId()]
        );
        $this->assertResponseContains('get_content_node_include_product.yml', $response);
    }

    public function testGetWithIncludeForProductCollectionContentVariant()
    {
        $response = $this->get(
            ['entity' => 'webcatalogtree', 'id' => '<toString(@catalog1_node1->id)>'],
            [
                'fields[webcatalogtree]' => 'content',
                'include'                => 'content'
            ]
        );
        $this->assertResponseContains('get_content_node_include_product_collection.yml', $response);
        self::assertCount(1, self::jsonToArray($response->getContent())['included']);
    }

    public function testGetWithIncludeForProductCollectionContentVariantWithProducts()
    {
        $response = $this->get(
            ['entity' => 'webcatalogtree', 'id' => '<toString(@catalog1_node1->id)>'],
            [
                'fields[webcatalogtree]' => 'content',
                'fields[productsearch]'  => 'name,product,productFamily',
                'fields[products]'       => 'sku,name,url,urls',
                'include'                => 'content.products.product'
            ]
        );
        $this->assertResponseContains('get_content_node_include_product_collection_products.yml', $response);
        self::assertCount(3, self::jsonToArray($response->getContent())['included']);
    }

    public function testGetWithIncludeForProductCollectionContentVariantWithPaginationLinks()
    {
        $response = $this->get(
            ['entity' => 'webcatalogtree', 'id' => '<toString(@catalog1_node12->id)>'],
            [
                'fields[webcatalogtree]' => 'content',
                'include'                => 'content'
            ],
            ['HTTP_HATEOAS' => true]
        );
        $expectedResponseData = [
            'included' => [
                [
                    'relationships' => [
                        'products' => [
                            'links' => [
                                'next' => $this->getApiBaseUrl()
                                    . '/productcollection/'
                                    . $this->getReference('catalog1_node12_variant')->getId()
                                    . '?page%5Bnumber%5D=2'
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $this->assertResponseContains($expectedResponseData, $response);
        self::assertCount(1, self::jsonToArray($response->getContent())['included']);
    }

    public function testGetRelationshipForMasterCatalogCategoryContent()
    {
        $response = $this->getRelationship(
            [
                'entity'      => 'webcatalogtree',
                'id'          => '<toString(@catalog1_node11->id)>',
                'association' => 'content'
            ]
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'mastercatalogcategories', 'id' => '<toString(@category1->id)>']],
            $response
        );
        self::assertArrayNotHasKey('attributes', self::jsonToArray($response->getContent())['data']);
    }

    public function testGetSubresourceForMasterCatalogCategoryContent()
    {
        $response = $this->getSubresource(
            [
                'entity'      => 'webcatalogtree',
                'id'          => '<toString(@catalog1_node11->id)>',
                'association' => 'content'
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'mastercatalogcategories',
                    'id'         => '<toString(@category1->id)>',
                    'attributes' => [
                        'title' => 'Category 1'
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetRelationshipForProductContent()
    {
        $response = $this->getRelationship(
            [
                'entity'      => 'webcatalogtree',
                'id'          => '<toString(@catalog1_node11->id)>',
                'association' => 'content'
            ],
            [],
            ['HTTP_X-Localization-ID' => $this->getReference('es')->getId()]
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'products', 'id' => '<toString(@product2->id)>']],
            $response
        );
        self::assertArrayNotHasKey('attributes', self::jsonToArray($response->getContent())['data']);
    }

    public function testGetSubresourceForProductContent()
    {
        $response = $this->getSubresource(
            [
                'entity'      => 'webcatalogtree',
                'id'          => '<toString(@catalog1_node11->id)>',
                'association' => 'content'
            ],
            [],
            ['HTTP_X-Localization-ID' => $this->getReference('es')->getId()]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'products',
                    'id'         => '<toString(@product2->id)>',
                    'attributes' => [
                        'name' => 'Product 2'
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetRelationshipForSystemPageContent()
    {
        $response = $this->getRelationship(
            [
                'entity'      => 'webcatalogtree',
                'id'          => '<toString(@catalog1_node111->id)>',
                'association' => 'content'
            ]
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'systempages', 'id' => 'oro_product_frontend_product_index']],
            $response
        );
        self::assertArrayNotHasKey('attributes', self::jsonToArray($response->getContent())['data']);
    }

    public function testGetSubresourceForSystemPageContent()
    {
        $response = $this->getSubresource(
            [
                'entity'      => 'webcatalogtree',
                'id'          => '<toString(@catalog1_node111->id)>',
                'association' => 'content'
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'systempages',
                    'id'         => 'oro_product_frontend_product_index',
                    'attributes' => [
                        'url' => '/product/'
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetRelationshipForProductCollectionContent()
    {
        $response = $this->getRelationship(
            [
                'entity'      => 'webcatalogtree',
                'id'          => '<toString(@catalog1_node12->id)>',
                'association' => 'content'
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'productcollection',
                    'id'   => '<toString(@catalog1_node12->contentVariants->first()->id)>'
                ]
            ],
            $response
        );
        self::assertArrayNotHasKey('relationships', self::jsonToArray($response->getContent())['data']);
    }

    public function testGetSubresourceForProductCollectionContent()
    {
        $response = $this->getSubresource(
            [
                'entity'      => 'webcatalogtree',
                'id'          => '<toString(@catalog1_node1->id)>',
                'association' => 'content'
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'productcollection',
                    'id'            => '<toString(@catalog1_node1->contentVariants->first()->id)>',
                    'relationships' => [
                        'products' => [
                            'data' => [
                                ['type' => 'productsearch', 'id' => '<toString(@product1->id)>']
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetSubresourceForProductCollectionContentDefaultCount()
    {
        $response = $this->getSubresource(
            [
                'entity'      => 'webcatalogtree',
                'id'          => '<toString(@catalog1_node12->id)>',
                'association' => 'content'
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'productcollection',
                    'id'   => '<toString(@catalog1_node12->contentVariants->first()->id)>'
                ]
            ],
            $response
        );
        self::assertCount(
            10,
            self::jsonToArray($response->getContent())['data']['relationships']['products']['data']
        );
    }

    public function testGetSubresourceForProductCollectionContentWithPaginationLinks()
    {
        $response = $this->getSubresource(
            [
                'entity'      => 'webcatalogtree',
                'id'          => '<toString(@catalog1_node12->id)>',
                'association' => 'content'
            ],
            [],
            ['HTTP_HATEOAS' => true]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'productcollection',
                    'id'            => '<toString(@catalog1_node12->contentVariants->first()->id)>',
                    'relationships' => [
                        'products' => [
                            'links' => [
                                'next' => $this->getApiBaseUrl()
                                    . '/productcollection/'
                                    . $this->getReference('catalog1_node12_variant')->getId()
                                    . '?page%5Bnumber%5D=2'
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
        self::assertCount(
            10,
            self::jsonToArray($response->getContent())['data']['relationships']['products']['data']
        );
    }
}
