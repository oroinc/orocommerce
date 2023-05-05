<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadBuyerCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\Api\FrontendRestJsonApiTestCase;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListTotalManager;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class ShoppingListForBuyerTest extends FrontendRestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadBuyerCustomerUserData::class,
            '@OroShoppingListBundle/Tests/Functional/Api/Frontend/DataFixtures/shopping_list.yml'
        ]);

        /** @var ShoppingListTotalManager $totalManager */
        $totalManager = self::getContainer()->get('oro_shopping_list.manager.shopping_list_total');
        for ($i = 1; $i <= 3; $i++) {
            $totalManager->recalculateTotals(
                $this->getReference(sprintf('shopping_list%d', $i)),
                true
            );
        }
    }

    public function testGetList()
    {
        $response = $this->cget(['entity' => 'shoppinglists'], [], ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains('cget_shopping_list_buyer.yml', $response);
        self::assertEquals(2, $response->headers->get('X-Include-Total-Count'));
    }

    public function testGet()
    {
        $response = $this->get(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>']
        );

        $this->assertResponseContains('get_shopping_list.yml', $response);
    }

    public function testTryToGetShoppingListFromAnotherWebsite()
    {
        $response = $this->get(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list3->id)>'],
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

    public function testTryToGetShoppingListFromAnotherCustomer()
    {
        $response = $this->get(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list4->id)>'],
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

    public function testTryToGetShoppingListFromAnotherCustomerUser()
    {
        $response = $this->get(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list2->id)>'],
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

    public function testUpdate()
    {
        $shoppingListId = $this->getReference('shopping_list1')->getId();
        $data = [
            'data' => [
                'type'       => 'shoppinglists',
                'id'         => (string)$shoppingListId,
                'attributes' => [
                    'name' => 'Updated Shopping List'
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'shoppinglists', 'id' => (string)$shoppingListId],
            $data
        );

        $this->assertResponseContains($data, $response);

        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getEntityManager()
            ->getRepository(ShoppingList::class)
            ->find($shoppingListId);
        self::assertNotNull($shoppingList);
        self::assertEquals('Updated Shopping List', $shoppingList->getLabel());
    }

    public function testTryToUpdateFromAnotherCustomerUser()
    {
        $shoppingListId = $this->getReference('shopping_list2')->getId();
        $data = [
            'data' => [
                'type'       => 'shoppinglists',
                'id'         => (string)$shoppingListId,
                'attributes' => [
                    'name' => 'Updated Shopping List'
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'shoppinglists', 'id' => (string)$shoppingListId],
            $data,
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

    public function testTryToUpdateFromAnotherWebsite()
    {
        $shoppingListId = $this->getReference('shopping_list3')->getId();
        $data = [
            'data' => [
                'type'       => 'shoppinglists',
                'id'         => (string)$shoppingListId,
                'attributes' => [
                    'name' => 'Updated Shopping List'
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'shoppinglists', 'id' => (string)$shoppingListId],
            $data,
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

    public function testTryToUpdateFromAnotherCustomer()
    {
        $shoppingListId = $this->getReference('shopping_list4')->getId();
        $data = [
            'data' => [
                'type'       => 'shoppinglists',
                'id'         => (string)$shoppingListId,
                'attributes' => [
                    'name' => 'Updated Shopping List'
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'shoppinglists', 'id' => (string)$shoppingListId],
            $data,
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

    public function testDelete()
    {
        $shoppingListId = $this->getReference('shopping_list1')->getId();

        $this->delete(
            ['entity' => 'shoppinglists', 'id' => (string)$shoppingListId]
        );

        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getEntityManager()
            ->getRepository(ShoppingList::class)
            ->find($shoppingListId);

        self::assertTrue(null === $shoppingList);
    }

    public function testTryToDeleteFromAnotherCustomerUser()
    {
        $shoppingListId = $this->getReference('shopping_list2')->getId();

        $response = $this->delete(
            ['entity' => 'shoppinglists', 'id' => (string)$shoppingListId],
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

    public function testTryToDeleteFromAnotherWebsite()
    {
        $shoppingListId = $this->getReference('shopping_list3')->getId();

        $response = $this->delete(
            ['entity' => 'shoppinglists', 'id' => (string)$shoppingListId],
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

    public function testTryToDeleteFromAnotherCustomer()
    {
        $shoppingListId = $this->getReference('shopping_list4')->getId();

        $response = $this->delete(
            ['entity' => 'shoppinglists', 'id' => (string)$shoppingListId],
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

    public function testGetRelationshipCustomer()
    {
        $response = $this->getRelationship(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>', 'association' => 'customer']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'customers',
                    'id'   => '<toString(@customer->id)>'
                ]
            ],
            $response
        );
    }

    public function testTryToGetRelationshipCustomerFromAnotherCustomerUser()
    {
        $response = $this->getRelationship(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list2->id)>', 'association' => 'customer'],
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

    public function testTryToGetRelationshipCustomerFromAnotherWebsite()
    {
        $response = $this->getRelationship(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list3->id)>', 'association' => 'customer'],
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

    public function testTryToGetRelationshipCustomerFromAnotherCustomer()
    {
        $response = $this->getRelationship(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list4->id)>', 'association' => 'customer'],
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

    public function testGetRelationshipCustomerUser()
    {
        $response = $this->getRelationship(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>', 'association' => 'customerUser']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'customerusers',
                    'id'   => '<toString(@customer_user->id)>'
                ]
            ],
            $response
        );
    }

    public function testTryToGetRelationshipCustomerUserFromAnotherCustomerUser()
    {
        $response = $this->getRelationship(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list2->id)>', 'association' => 'customerUser'],
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

    public function testTryToGetRelationshipCustomerUserFromAnotherWebsite()
    {
        $response = $this->getRelationship(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list3->id)>', 'association' => 'customerUser'],
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

    public function testTryToGetRelationshipCustomerUserFromAnotherCustomer()
    {
        $response = $this->getRelationship(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list4->id)>', 'association' => 'customerUser'],
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

    public function testGetRelationshipItems()
    {
        $response = $this->getRelationship(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>', 'association' => 'items']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'shoppinglistitems', 'id' => '<toString(@line_item1->id)>'],
                    ['type' => 'shoppinglistitems', 'id' => '<toString(@line_item2->id)>'],
                    ['type' => 'shoppinglistitems', 'id' => '<toString(@kit_line_item1->id)>'],
                ]
            ],
            $response
        );
    }

    public function testGetSubresourceCustomer()
    {
        $response = $this->getSubresource(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>', 'association' => 'customer']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'customers',
                    'id'         => '<toString(@customer->id)>',
                    'attributes' => [
                        'name' => 'Customer'
                    ]
                ]
            ],
            $response
        );
    }
}
