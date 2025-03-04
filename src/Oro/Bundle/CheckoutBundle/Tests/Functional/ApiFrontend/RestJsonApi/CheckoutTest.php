<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCheckoutData;
use Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCompetedCheckoutData;
use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CheckoutTest extends FrontendRestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            LoadCheckoutData::class,
            LoadCompetedCheckoutData::class
        ]);
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'checkouts']);
        $this->assertResponseContains('cget_checkout.yml', $response);
    }

    public function testTryToGetForDeleted(): void
    {
        $response = $this->get(
            ['entity' => 'checkouts', 'id' => '<toString(@checkout.deleted->id)>'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testGet(): void
    {
        $response = $this->get(['entity' => 'checkouts', 'id' => '<toString(@checkout.completed->id)>']);
        $this->assertResponseContains('get_checkout.yml', $response);
    }

    public function testGetWithTotalsOnly(): void
    {
        $response = $this->get(
            ['entity' => 'checkouts', 'id' => '<toString(@checkout.completed->id)>'],
            ['fields[checkouts]' => 'totalValue,totals']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'checkouts',
                    'id' => '<toString(@checkout.completed->id)>',
                    'attributes' => [
                        'totalValue' => '90.4500',
                        'totals' => [
                            ['subtotalType' => 'subtotal', 'description' => 'Subtotal', 'amount' => '100.5000'],
                            ['subtotalType' => 'discount', 'description' => 'Discount', 'amount' => '-20.0500'],
                            ['subtotalType' => 'shipping_cost', 'description' => 'Shipping', 'amount' => '10.0000']
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetWithCouponsOnly(): void
    {
        $response = $this->get(
            ['entity' => 'checkouts', 'id' => '<toString(@checkout.completed->id)>'],
            ['fields[checkouts]' => 'coupons']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'checkouts',
                    'id' => '<toString(@checkout.completed->id)>',
                    'attributes' => [
                        'coupons' => [
                            [
                                'couponCode' => 'coupon_with_promo_and_valid_from_and_until',
                                'description' => 'Order percent promotion name'
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetFromAnotherCustomerUser(): void
    {
        $response = $this->get(['entity' => 'checkouts', 'id' => '<toString(@checkout.another_customer_user->id)>']);
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'checkouts',
                    'id' => '<toString(@checkout.another_customer_user->id)>'
                ]
            ],
            $response
        );
    }

    public function testTryToGetFromAnotherDepartment(): void
    {
        $response = $this->get(
            ['entity' => 'checkouts', 'id' => '<toString(@checkout.another_department_customer_user->id)>'],
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

    public function testGetWithIncludeFilterForSource(): void
    {
        $response = $this->get(
            ['entity' => 'checkouts', 'id' => '<toString(@checkout.completed->id)>'],
            [
                'include' => 'source',
                'fields[checkouts]' => 'currency,source',
                'fields[shoppinglists]' => 'name,customerUser'
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'checkouts',
                    'id' => '<toString(@checkout.completed->id)>',
                    'attributes' => [
                        'currency' => 'USD'
                    ],
                    'relationships' => [
                        'source' => [
                            'data' => [
                                'type' => 'shoppinglists',
                                'id' => '<toString(@checkout.completed.shopping_list->id)>'
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'shoppinglists',
                        'id' => '<toString(@checkout.completed.shopping_list->id)>',
                        'attributes' => [
                            'name' => 'checkout.completed_label'
                        ],
                        'relationships' => [
                            'customerUser' => [
                                'data' => [
                                    'type' => 'customerusers',
                                    'id' => '<toString(@customer_user->id)>'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
        $responseData = self::jsonToArray($response->getContent());
        self::assertCount(1, $responseData['data']['attributes'], 'primary entity attributes');
        self::assertCount(1, $responseData['data']['relationships'], 'primary entity relationships');
        self::assertCount(1, $responseData['included'], 'included entities');
        self::assertCount(1, $responseData['included'][0]['attributes'], 'included entity attributes');
        self::assertCount(1, $responseData['included'][0]['relationships'], 'included entity relationships');
    }

    public function testGetWithIncludeFilterForOrder(): void
    {
        $response = $this->get(
            ['entity' => 'checkouts', 'id' => '<toString(@checkout.completed->id)>'],
            [
                'include' => 'order',
                'fields[checkouts]' => 'currency,order',
                'fields[orders]' => 'identifier,customerNotes,customerUser'
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'checkouts',
                    'id' => '<toString(@checkout.completed->id)>',
                    'attributes' => [
                        'currency' => 'USD'
                    ],
                    'relationships' => [
                        'order' => [
                            'data' => [
                                'type' => 'orders',
                                'id' => '<toString(@checkout.completed.order->id)>'
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'orders',
                        'id' => '<toString(@checkout.completed.order->id)>',
                        'attributes' => [
                            'identifier' => '<toString(@checkout.completed.order->id)>',
                            'customerNotes' => 'checkout.completed'
                        ],
                        'relationships' => [
                            'customerUser' => [
                                'data' => [
                                    'type' => 'customerusers',
                                    'id' => '<toString(@customer_user->id)>'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
        $responseData = self::jsonToArray($response->getContent());
        self::assertCount(1, $responseData['data']['attributes'], 'primary entity attributes');
        self::assertCount(1, $responseData['data']['relationships'], 'primary entity relationships');
        self::assertCount(1, $responseData['included'], 'included entities');
        self::assertCount(2, $responseData['included'][0]['attributes'], 'included entity attributes');
        self::assertCount(1, $responseData['included'][0]['relationships'], 'included entity relationships');
    }

    public function testGetOnlyTotalValue(): void
    {
        $response = $this->get(
            ['entity' => 'checkouts', 'id' => '<toString(@checkout.completed->id)>'],
            ['fields[checkouts]' => 'totalValue']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'checkouts',
                    'id' => '<toString(@checkout.completed->id)>',
                    'attributes' => [
                        'totalValue' => '90.4500'
                    ]
                ]
            ],
            $response
        );
        $responseData = self::jsonToArray($response->getContent());
        self::assertCount(1, $responseData['data']['attributes']);
        self::assertArrayNotHasKey('relationships', $responseData['data']);
    }

    public function testGetOnlyTotals(): void
    {
        $response = $this->get(
            ['entity' => 'checkouts', 'id' => '<toString(@checkout.completed->id)>'],
            ['fields[checkouts]' => 'totals']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'checkouts',
                    'id' => '<toString(@checkout.completed->id)>',
                    'attributes' => [
                        'totals' => [
                            ['subtotalType' => 'subtotal', 'description' => 'Subtotal', 'amount' => '100.5000'],
                            ['subtotalType' => 'shipping_cost', 'description' => 'Shipping', 'amount' => '10.0000']
                        ]
                    ]
                ]
            ],
            $response
        );
        $responseData = self::jsonToArray($response->getContent());
        self::assertCount(1, $responseData['data']['attributes']);
        self::assertArrayNotHasKey('relationships', $responseData['data']);
    }

    public function testTryToGetSubresourceForDeleted(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'checkouts', 'id' => '<toString(@checkout.deleted->id)>', 'association' => 'lineItems'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToGetRelationshipForDeleted(): void
    {
        $response = $this->getRelationship(
            ['entity' => 'checkouts', 'id' => '<toString(@checkout.deleted->id)>', 'association' => 'lineItems'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testGetSubresourceForSource(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'checkouts', 'id' => '<toString(@checkout.completed->id)>', 'association' => 'source']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'shoppinglists',
                    'id' => '<toString(@checkout.completed.shopping_list->id)>',
                    'attributes' => [
                        'name' => 'checkout.completed_label',
                        'notes' => 'checkout.completed_notes'
                    ],
                    'relationships' => [
                        'customerUser' => [
                            'data' => [
                                'type' => 'customerusers',
                                'id' => '<toString(@customer_user->id)>'
                            ]
                        ],
                        'customer' => [
                            'data' => [
                                'type' => 'customers',
                                'id' => '<toString(@customer->id)>'
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetSubresourceForSourceWithIncludeFilter(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'checkouts', 'id' => '<toString(@checkout.completed->id)>', 'association' => 'source'],
            [
                'include' => 'customerUser',
                'fields[shoppinglists]' => 'name,customerUser',
                'fields[customerusers]' => 'email,customer'
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'shoppinglists',
                    'id' => '<toString(@checkout.completed.shopping_list->id)>',
                    'attributes' => [
                        'name' => 'checkout.completed_label'
                    ],
                    'relationships' => [
                        'customerUser' => [
                            'data' => [
                                'type' => 'customerusers',
                                'id' => '<toString(@customer_user->id)>'
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'customerusers',
                        'id' => '<toString(@customer_user->id)>',
                        'attributes' => [
                            'email' => '@customer_user->email'
                        ],
                        'relationships' => [
                            'customer' => [
                                'data' => [
                                    'type' => 'customers',
                                    'id' => '<toString(@customer->id)>'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
        $responseData = self::jsonToArray($response->getContent());
        self::assertCount(1, $responseData['data']['attributes'], 'primary entity attributes');
        self::assertCount(1, $responseData['data']['relationships'], 'primary entity relationships');
        self::assertCount(1, $responseData['included'], 'included entities');
        self::assertCount(1, $responseData['included'][0]['attributes'], 'included entity attributes');
        self::assertCount(1, $responseData['included'][0]['relationships'], 'included entity relationships');
    }

    public function testGetRelationshipForSource(): void
    {
        $response = $this->getRelationship(
            ['entity' => 'checkouts', 'id' => '<toString(@checkout.completed->id)>', 'association' => 'source']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'shoppinglists',
                    'id' => '<toString(@checkout.completed.shopping_list->id)>'
                ]
            ],
            $response
        );
        $responseData = self::jsonToArray($response->getContent());
        self::assertArrayNotHasKey('attributes', $responseData['data']);
        self::assertArrayNotHasKey('relationships', $responseData['data']);
    }

    public function testGetSubresourceForOrder(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'checkouts', 'id' => '<toString(@checkout.completed->id)>', 'association' => 'order']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'orders',
                    'id' => '<toString(@checkout.completed.order->id)>',
                    'attributes' => [
                        'identifier' => '<toString(@checkout.completed.order->id)>',
                        'customerNotes' => 'checkout.completed'
                    ],
                    'relationships' => [
                        'customerUser' => [
                            'data' => [
                                'type' => 'customerusers',
                                'id' => '<toString(@customer_user->id)>'
                            ]
                        ],
                        'customer' => [
                            'data' => [
                                'type' => 'customers',
                                'id' => '<toString(@customer->id)>'
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetSubresourceForOrderWithIncludeFilter(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'checkouts', 'id' => '<toString(@checkout.completed->id)>', 'association' => 'order'],
            [
                'include' => 'customerUser',
                'fields[orders]' => 'identifier,customerNotes,customerUser',
                'fields[customerusers]' => 'email,customer'
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'orders',
                    'id' => '<toString(@checkout.completed.order->id)>',
                    'attributes' => [
                        'identifier' => '<toString(@checkout.completed.order->id)>',
                        'customerNotes' => 'checkout.completed'
                    ],
                    'relationships' => [
                        'customerUser' => [
                            'data' => [
                                'type' => 'customerusers',
                                'id' => '<toString(@customer_user->id)>'
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'customerusers',
                        'id' => '<toString(@customer_user->id)>',
                        'attributes' => [
                            'email' => '@customer_user->email'
                        ],
                        'relationships' => [
                            'customer' => [
                                'data' => [
                                    'type' => 'customers',
                                    'id' => '<toString(@customer->id)>'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
        $responseData = self::jsonToArray($response->getContent());
        self::assertCount(2, $responseData['data']['attributes'], 'primary entity attributes');
        self::assertCount(1, $responseData['data']['relationships'], 'primary entity relationships');
        self::assertCount(1, $responseData['included'], 'included entities');
        self::assertCount(1, $responseData['included'][0]['attributes'], 'included entity attributes');
        self::assertCount(1, $responseData['included'][0]['relationships'], 'included entity relationships');
    }

    public function testGetRelationshipForOrder(): void
    {
        $response = $this->getRelationship(
            ['entity' => 'checkouts', 'id' => '<toString(@checkout.completed->id)>', 'association' => 'order']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'orders',
                    'id' => '<toString(@checkout.completed.order->id)>'
                ]
            ],
            $response
        );
        $responseData = self::jsonToArray($response->getContent());
        self::assertArrayNotHasKey('attributes', $responseData['data']);
        self::assertArrayNotHasKey('relationships', $responseData['data']);
    }
}
