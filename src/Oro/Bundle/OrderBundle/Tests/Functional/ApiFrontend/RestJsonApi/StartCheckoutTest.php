<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadPaymentOrderStatuses;
use Oro\Bundle\OrderBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadPaymentTransactions;
use Oro\Bundle\OrderBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadShippingMethods;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
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
            '@OroOrderBundle/Tests/Functional/ApiFrontend/DataFixtures/orders.yml',
            LoadShippingMethods::class,
            LoadPaymentOrderStatuses::class,
            LoadPaymentTransactions::class
        ]);
    }

    public function testOptionsForStartCheckout(): void
    {
        $response = $this->options(
            $this->getSubresourceRouteName(),
            ['entity' => 'orders', 'id' => '1', 'association' => 'checkout']
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, POST');
    }

    public function testTryToStartCheckoutForNotExistingOrder(): void
    {
        $response = $this->postSubresource(
            ['entity' => 'orders', 'id' => '999999', 'association' => 'checkout'],
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

    public function testTryToStartCheckoutForNotAccessibleOrder(): void
    {
        $response = $this->postSubresource(
            ['entity' => 'orders', 'id' => '<toString(@another_order->id)>', 'association' => 'checkout'],
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
            ['entity' => 'orders', 'id' => '<toString(@order4->id)>', 'association' => 'checkout'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, POST');
    }

    public function testTryToDeleteSubresourceForStartCheckout(): void
    {
        $response = $this->deleteSubresource(
            ['entity' => 'orders', 'id' => '<toString(@order4->id)>', 'association' => 'checkout'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, POST');
    }

    public function testTryToGetSubresourceForStartCheckout(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => '<toString(@order4->id)>', 'association' => 'checkout'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, POST');
    }

    public function testTryToStartCheckoutWithInvalidValueForActualizeOption(): void
    {
        $response = $this->postSubresource(
            ['entity' => 'orders', 'id' => '<toString(@order4->id)>', 'association' => 'checkout'],
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
            ['entity' => 'orders', 'id' => '<toString(@order4->id)>', 'association' => 'checkout'],
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
                        'customerNotes' => null,
                        'currency' => 'USD',
                        'completed' => false,
                        'totalValue' => '190.4400',
                        'totals' => [
                            ['subtotalType' => 'subtotal', 'description' => 'Subtotal', 'amount' => '190.4400']
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
                            'data' => ['type' => 'orders', 'id' => '<toString(@order4->id)>']
                        ],
                        'order' => ['data' => null]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'checkoutlineitems',
                        'id' => 'new',
                        'attributes' => [
                            'productSku' => 'product-kit-2'
                        ],
                        'relationships' => [
                            'product' => [
                                'data' => ['type' => 'products', 'id' => '<toString(@product-kit-2->id)>']
                            ],
                            'kitItemLineItems' => [
                                'data' => [
                                    ['type' => 'checkoutproductkititemlineitems', 'id' => 'new']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'checkoutlineitems',
                        'id' => 'new',
                        'attributes' => [
                            'productSku' => 'product-kit-3'
                        ],
                        'relationships' => [
                            'product' => [
                                'data' => ['type' => 'products', 'id' => '<toString(@product-kit-3->id)>']
                            ],
                            'kitItemLineItems' => [
                                'data' => [
                                    ['type' => 'checkoutproductkititemlineitems', 'id' => 'new'],
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
                            'productSku' => 'product-kit-2'
                        ],
                        'relationships' => [
                            'product' => [
                                'data' => ['type' => 'products', 'id' => '<toString(@product-kit-2->id)>']
                            ],
                            'kitItemLineItems' => [
                                'data' => [
                                    ['type' => 'checkoutproductkititemlineitems', 'id' => 'new'],
                                    ['type' => 'checkoutproductkititemlineitems', 'id' => 'new']
                                ]
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
        self::assertCount(1, $responseData['included'][2]['attributes']);
        self::assertCount(2, $responseData['included'][2]['relationships']);

        return $expectedData;
    }

    /**
     * @depends testStartCheckout
     */
    public function testStartCheckoutWhenCheckoutAlreadyExists(array $expectedData): array
    {
        $response = $this->postSubresource(
            ['entity' => 'orders', 'id' => '<toString(@order4->id)>', 'association' => 'checkout'],
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
            ['entity' => 'orders', 'id' => '<toString(@order4->id)>', 'association' => 'checkout'],
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
        $orderId = (int)$expectedData['data']['relationships']['source']['data']['id'];
        $em = $this->getEntityManager();
        /** @var Order $order */
        $order = $em->find(Order::class, $orderId);
        $order->setPoNumber('UPDATED PO NUMBER');
        $order->setCustomerNotes('updated notes');
        $firstLineItem = $order->getLineItems()->first();
        foreach ($order->getLineItems() as $lineItem) {
            if ($lineItem !== $firstLineItem) {
                $order->removeLineItem($lineItem);
            }
        }
        $em->flush();

        $response = $this->postSubresource(
            ['entity' => 'orders', 'id' => '<toString(@order4->id)>', 'association' => 'checkout'],
            ['meta' => ['actualize' => true]]
        );

        unset(
            $expectedData['data']['relationships']['lineItems']['data'][0]['id'],
            $expectedData['data']['relationships']['lineItems']['data'][1],
            $expectedData['data']['relationships']['lineItems']['data'][2],
            $expectedData['included']
        );
        $expectedData['data']['attributes']['customerNotes'] = null;
        $expectedData['data']['attributes']['totalValue'] = '11.5900';
        $expectedData['data']['attributes']['totals'][0]['amount'] = '11.5900';
        $this->assertResponseContains($expectedData, $response);
        self::assertResponseStatusCodeEquals($response, Response::HTTP_OK);
    }

    /**
     * @depends testStartCheckoutWhenCheckoutAlreadyExistsAndActualizationWasRequested
     */
    public function testStartCheckoutWhenNoEditPermissionToEditOrder(): void
    {
        $this->updateRolePermissions(
            $this->getReference('admin')->getRole(),
            Order::class,
            [
                'VIEW' => AccessLevel::SYSTEM_LEVEL,
                'CREATE' => AccessLevel::SYSTEM_LEVEL,
                'EDIT' => AccessLevel::NONE_LEVEL,
                'DELETE' => AccessLevel::SYSTEM_LEVEL
            ]
        );

        $response = $this->postSubresource(
            ['entity' => 'orders', 'id' => '<toString(@order4->id)>', 'association' => 'checkout']
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_OK);
    }

    /**
     * @depends testStartCheckoutWhenNoEditPermissionToEditOrder
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
            ['entity' => 'orders', 'id' => '<toString(@order4->id)>', 'association' => 'checkout'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }
}
