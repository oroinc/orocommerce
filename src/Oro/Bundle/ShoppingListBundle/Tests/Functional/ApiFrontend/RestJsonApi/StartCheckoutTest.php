<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class StartCheckoutTest extends FrontendRestJsonApiTestCase
{
    use RolePermissionExtension;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            '@OroShoppingListBundle/Tests/Functional/ApiFrontend/DataFixtures/shopping_list.yml',
        ]);
    }

    public function testOptionsForStartCheckout(): void
    {
        $response = $this->options(
            $this->getSubresourceRouteName(),
            ['entity' => 'shoppinglists', 'id' => '1', 'association' => 'checkout']
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, POST');
    }

    public function testTryToStartCheckoutForNotExistingShoppingList(): void
    {
        $response = $this->postSubresource(
            ['entity' => 'shoppinglists', 'id' => '999999', 'association' => 'checkout'],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'not found http exception',
                'detail' => 'The parent entity does not exist.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
        );
    }

    public function testTryToStartCheckoutForNotAccessibleShoppingList(): void
    {
        $response = $this->postSubresource(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list3->id)>', 'association' => 'checkout'],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'No access to the parent entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToUpdateSubresourceForStartCheckout(): void
    {
        $response = $this->patchSubresource(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>', 'association' => 'checkout'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, POST');
    }

    public function testTryToDeleteSubresourceForStartCheckout(): void
    {
        $response = $this->deleteSubresource(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>', 'association' => 'checkout'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, POST');
    }

    public function testTryToGetSubresourceForStartCheckout(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>', 'association' => 'checkout'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, POST');
    }

    public function testTryToStartCheckoutWithInvalidValueForActualizeOption(): void
    {
        $response = $this->postSubresource(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>', 'association' => 'checkout'],
            ['meta' => ['actualize' => 'test']],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'form constraint',
                'detail' => 'This value is not valid.',
                'source' => ['pointer' => '/meta/actualize']
            ],
            $response
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testStartCheckout(): array
    {
        $response = $this->postSubresource(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>', 'association' => 'checkout'],
            ['filters' => 'include=lineItems&fields[checkoutlineitems]=productSku,product,kitItemLineItems']
        );
        $expectedData = $this->updateResponseContent(
            [
                'data' => [
                    'type' => 'checkouts',
                    'id' => 'new',
                    'attributes' => [
                        'poNumber' => null,
                        'shippingMethod' => null,
                        'shippingMethodType' => null,
                        'paymentMethod' => null,
                        'shipUntil' => null,
                        'customerNotes' => 'Shopping List 1 Notes',
                        'currency' => 'USD',
                        'completed' => false,
                        'totalValue' => '59.1500',
                        'totals' => [
                            ['subtotalType' => 'subtotal', 'description' => 'Subtotal', 'amount' => '59.1500']
                        ]
                    ],
                    'relationships' => [
                        'lineItems' => [
                            'data' => [
                                ['type' => 'checkoutlineitems', 'id' => 'new'],
                                ['type' => 'checkoutlineitems', 'id' => 'new'],
                                ['type' => 'checkoutlineitems', 'id' => 'new']
                            ]
                        ],
                        'customerUser' => [
                            'data' => ['type' => 'customerusers', 'id' => '<toString(@customer_user->id)>']
                        ],
                        'customer' => [
                            'data' => ['type' => 'customers', 'id' => '<toString(@customer->id)>']
                        ],
                        'billingAddress' => ['data' => null],
                        'shippingAddress' => ['data' => null],
                        'source' => [
                            'data' => ['type' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>']
                        ],
                        'order' => ['data' => null]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'checkoutlineitems',
                        'id' => 'new',
                        'attributes' => [
                            'productSku' => 'KIT1'
                        ],
                        'relationships' => [
                            'product' => [
                                'data' => ['type' => 'products', 'id' => '<toString(@product_kit1->id)>']
                            ],
                            'kitItemLineItems' => [
                                'data' => [
                                    ['type' => 'checkoutproductkititemlineitems', 'id' => 'new'],
                                    ['type' => 'checkoutproductkititemlineitems', 'id' => 'new']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'checkoutlineitems',
                        'id' => 'new',
                        'attributes' => [
                            'productSku' => 'PSKU1'
                        ],
                        'relationships' => [
                            'product' => [
                                'data' => ['type' => 'products', 'id' => '<toString(@product1->id)>']
                            ],
                            'kitItemLineItems' => [
                                'data' => []
                            ]
                        ]
                    ],
                    [
                        'type' => 'checkoutlineitems',
                        'id' => 'new',
                        'attributes' => [
                            'productSku' => 'PSKU2'
                        ],
                        'relationships' => [
                            'product' => [
                                'data' => ['type' => 'products', 'id' => '<toString(@product2->id)>']
                            ],
                            'kitItemLineItems' => [
                                'data' => []
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
        $this->assertResponseContains($expectedData, $response);
        self::assertResponseStatusCodeEquals($response, Response::HTTP_CREATED);
        $responseData = self::jsonToArray($response->getContent());
        self::assertCount(3, $responseData['included']);
        self::assertCount(1, $responseData['included'][0]['attributes']);
        self::assertCount(2, $responseData['included'][0]['relationships']);

        return $expectedData;
    }

    /**
     * @depends testStartCheckout
     */
    public function testStartCheckoutWhenCheckoutAlreadyExists(array $expectedData): array
    {
        $response = $this->postSubresource(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>', 'association' => 'checkout'],
            ['filters' => 'include=lineItems&fields[checkoutlineitems]=productSku,product,kitItemLineItems']
        );
        $this->assertResponseContains($expectedData, $response);
        self::assertResponseStatusCodeEquals($response, Response::HTTP_OK);

        return $expectedData;
    }

    /**
     * @depends testStartCheckoutWhenCheckoutAlreadyExists
     */
    public function testStartCheckoutWhenCheckoutAlreadyExistsAndActualizationWasNotRequestedExplicitly(
        array $expectedData
    ): array {
        $response = $this->postSubresource(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>', 'association' => 'checkout'],
            [
                'filters' => 'include=lineItems&fields[checkoutlineitems]=productSku,product,kitItemLineItems',
                'meta' => ['actualize' => false]
            ]
        );
        $this->assertResponseContains($expectedData, $response);
        self::assertResponseStatusCodeEquals($response, Response::HTTP_OK);

        return $expectedData;
    }

    /**
     * @depends testStartCheckoutWhenCheckoutAlreadyExistsAndActualizationWasNotRequestedExplicitly
     */
    public function testStartCheckoutWhenCheckoutAlreadyExistsAndActualizationWasRequested(array $expectedData): void
    {
        $shoppingListId = (int)$expectedData['data']['relationships']['source']['data']['id'];
        $em = $this->getEntityManager();
        /** @var ShoppingList $shoppingList */
        $shoppingList = $em->find(ShoppingList::class, $shoppingListId);
        $shoppingList->setLabel('updated label');
        $shoppingList->setNotes('updated notes');
        $firstLineItem = $shoppingList->getLineItems()->first();
        foreach ($shoppingList->getLineItems() as $lineItem) {
            if ($lineItem !== $firstLineItem) {
                $shoppingList->removeLineItem($lineItem);
                $em->remove($lineItem);
            }
        }
        $em->flush();

        $response = $this->postSubresource(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>', 'association' => 'checkout'],
            ['meta' => ['actualize' => true]]
        );

        unset(
            $expectedData['data']['relationships']['lineItems']['data'][0]['id'],
            $expectedData['data']['relationships']['lineItems']['data'][1],
            $expectedData['data']['relationships']['lineItems']['data'][2],
            $expectedData['included']
        );
        $expectedData['data']['attributes']['customerNotes'] = 'updated notes';
        $expectedData['data']['attributes']['totalValue'] = '29.6000';
        $expectedData['data']['attributes']['totals'][0]['amount'] = '29.6000';
        $this->assertResponseContains($expectedData, $response);
        self::assertResponseStatusCodeEquals($response, Response::HTTP_OK);
    }

    /**
     * @depends testStartCheckoutWhenCheckoutAlreadyExistsAndActualizationWasRequested
     */
    public function testStartCheckoutWhenNoEditPermissionToEditShoppingList(): void
    {
        $this->updateRolePermissions(
            $this->getReference('admin')->getRole(),
            ShoppingList::class,
            [
                'VIEW' => AccessLevel::SYSTEM_LEVEL,
                'CREATE' => AccessLevel::SYSTEM_LEVEL,
                'EDIT' => AccessLevel::NONE_LEVEL,
                'DELETE' => AccessLevel::SYSTEM_LEVEL
            ]
        );

        $response = $this->postSubresource(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>', 'association' => 'checkout']
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_OK);
    }

    /**
     * @depends testStartCheckoutWhenNoEditPermissionToEditShoppingList
     */
    public function testTryToStartCheckoutWhenNoPermissionToCreateCheckout(): void
    {
        $this->updateRolePermissions(
            $this->getReference('admin')->getRole(),
            Checkout::class,
            [
                'VIEW' => AccessLevel::SYSTEM_LEVEL,
                'CREATE' => AccessLevel::NONE_LEVEL,
                'EDIT' => AccessLevel::SYSTEM_LEVEL,
                'DELETE' => AccessLevel::SYSTEM_LEVEL
            ]
        );

        $response = $this->postSubresource(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>', 'association' => 'checkout'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }
}
