<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Oro\Bundle\OrderBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadPaymentOrderStatuses;
use Oro\Bundle\OrderBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadPaymentTransactions;
use Oro\Bundle\OrderBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadShippingMethods;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class OrderTest extends FrontendRestJsonApiTestCase
{
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

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'orders']);

        $this->assertResponseContains('cget_order.yml', $response);
    }

    public function testGetListFilteredByIdentifier(): void
    {
        $response = $this->cget(['entity' => 'orders'], ['filter[identifier]' => 'Order2']);

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'orders',
                        'id'         => '<toString(@order2->id)>',
                        'attributes' => [
                            'identifier' => 'order2',
                            'poNumber'   => 'PO2'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListFilteredByIdentifierNeq(): void
    {
        $response = $this->cget(['entity' => 'orders'], ['filter[identifier][neq]' => 'Order2']);

        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'orders', 'id' => '<toString(@order1->id)>'],
                    ['type' => 'orders', 'id' => '<toString(@order3->id)>'],
                    ['type' => 'orders', 'id' => '<toString(@order4->id)>'],
                    ['type' => 'orders', 'id' => '<toString(@order5->id)>']
                ]
            ],
            $response
        );
    }

    public function testGetListFilteredBySeveralIdentifiers(): void
    {
        $response = $this->cget(['entity' => 'orders'], ['filter[identifier]' => 'Order2,Order3']);

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'orders',
                        'id'         => '<toString(@order2->id)>',
                        'attributes' => [
                            'identifier' => 'order2',
                            'poNumber'   => 'PO2'
                        ]
                    ],
                    [
                        'type'       => 'orders',
                        'id'         => '<toString(@order3->id)>',
                        'attributes' => [
                            'identifier' => 'order3',
                            'poNumber'   => 'PO3'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListFilteredBySeveralIdentifiersNeq(): void
    {
        $response = $this->cget(['entity' => 'orders'], ['filter[identifier][neq]' => 'Order2,Order3']);

        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'orders', 'id' => '<toString(@order1->id)>'],
                    ['type' => 'orders', 'id' => '<toString(@order4->id)>'],
                    ['type' => 'orders', 'id' => '<toString(@order5->id)>']
                ]
            ],
            $response
        );
    }

    public function testGetListFilteredByPoNumber(): void
    {
        $response = $this->cget(['entity' => 'orders'], ['filter[poNumber]' => 'po2']);

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'orders',
                        'id'         => '<toString(@order2->id)>',
                        'attributes' => [
                            'identifier' => 'order2',
                            'poNumber'   => 'PO2'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListFilteredByPoNumberNeq(): void
    {
        $response = $this->cget(['entity' => 'orders'], ['filter[poNumber][neq]' => 'po2']);

        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'orders', 'id' => '<toString(@order1->id)>'],
                    ['type' => 'orders', 'id' => '<toString(@order3->id)>'],
                    ['type' => 'orders', 'id' => '<toString(@order4->id)>'],
                    ['type' => 'orders', 'id' => '<toString(@order5->id)>']
                ]
            ],
            $response
        );
    }

    public function testGetListFilteredBySeveralPoNumbers(): void
    {
        $response = $this->cget(['entity' => 'orders'], ['filter[poNumber]' => 'po2,po3']);

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'orders',
                        'id'         => '<toString(@order2->id)>',
                        'attributes' => [
                            'identifier' => 'order2',
                            'poNumber'   => 'PO2'
                        ]
                    ],
                    [
                        'type'       => 'orders',
                        'id'         => '<toString(@order3->id)>',
                        'attributes' => [
                            'identifier' => 'order3',
                            'poNumber'   => 'PO3'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListFilteredBySeveralPoNumbersNeq(): void
    {
        $response = $this->cget(['entity' => 'orders'], ['filter[poNumber][neq]' => 'po2,po3']);

        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'orders', 'id' => '<toString(@order1->id)>'],
                    ['type' => 'orders', 'id' => '<toString(@order4->id)>'],
                    ['type' => 'orders', 'id' => '<toString(@order5->id)>']
                ]
            ],
            $response
        );
    }

    public function testGet(): void
    {
        $response = $this->get(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>']
        );

        $this->assertResponseContains('get_order.yml', $response);
    }

    public function testGetOrderWithProductKitLineItem(): void
    {
        $response = $this->get(
            ['entity' => 'orders', 'id' => '<toString(@order4->id)>']
        );

        $this->assertResponseContains('get_order_with_product_kit.yml', $response);
    }

    public function testGetForChildCustomer(): void
    {
        $response = $this->get(
            ['entity' => 'orders', 'id' => '<toString(@order3->id)>']
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'orders', 'id' => '<toString(@order3->id)>']],
            $response
        );
    }

    public function testTryToGetForCustomerFromAnotherDepartment(): void
    {
        $response = $this->get(
            ['entity' => 'orders', 'id' => '<toString(@another_order->id)>'],
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

    public function testGetShouldReturnCorrectShippingMethodAmountEvenIfOtherFieldsWereNotRequested(): void
    {
        $response = $this->get(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>'],
            ['fields[orders]' => 'shippingMethod']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'orders',
                    'id'         => '<toString(@order1->id)>',
                    'attributes' => [
                        'shippingMethod' => [
                            'code'  => '<("flat_rate_" . @flat_rate_shipping_channel->id)>',
                            'label' => 'Flat Rate'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetShouldReturnCorrectShippingCostAmountEvenIfOtherFieldsWereNotRequested(): void
    {
        $response = $this->get(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>'],
            ['fields[orders]' => 'shippingCostAmount']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'orders',
                    'id'         => '<toString(@order1->id)>',
                    'attributes' => [
                        'shippingCostAmount' => '7.0000'
                    ]
                ]
            ],
            $response
        );
    }

    public function testTryToUpdate(): void
    {
        $response = $this->patch(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDelete(): void
    {
        $response = $this->delete(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteList(): void
    {
        $response = $this->cdelete(
            ['entity' => 'orders'],
            ['filter' => ['id' => '<toString(@order1->id)>']],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET, POST');
    }

    /**
     * @dataProvider getLineItemsDataProvider
     */
    public function testGetSubresourceForLineItems(string $orderReference, array $expectedLineItemsData): void
    {
        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => $orderReference, 'association' => 'lineItems']
        );
        $this->assertResponseContains(
            [
                'data' => $expectedLineItemsData,
            ],
            $response
        );
    }

    /**
     * @dataProvider getLineItemsDataProvider
     */
    public function testGetRelationshipForLineItems(string $orderReference, array $expectedLineItemsData): void
    {
        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => $orderReference, 'association' => 'lineItems']
        );
        $this->assertResponseContains(
            [
                'data' => $expectedLineItemsData,
            ],
            $response
        );
    }

    public function getLineItemsDataProvider(): array
    {
        return [
            'order' => [
                'orderReference' => '<toString(@order1->id)>',
                'expectedLineItemsData' => [
                    ['type' => 'orderlineitems', 'id' => '<toString(@order1_line_item1->id)>'],
                    ['type' => 'orderlineitems', 'id' => '<toString(@order1_line_item2->id)>'],
                ],
            ],
            'order with product kit line items' => [
                'orderReference' => '<toString(@order4->id)>',
                'expectedLineItemsData' => [
                    ['type' => 'orderlineitems', 'id' => '<toString(@product_kit_2_line_item.1->id)>'],
                    ['type' => 'orderlineitems', 'id' => '<toString(@product_kit_3_line_item.1->id)>'],
                    ['type' => 'orderlineitems', 'id' => '<toString(@product_kit_2_line_item.2->id)>'],
                ],
            ],
        ];
    }

    public function testGetSubresourceForLineItemsOfOrderForChildCustomer(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => '<toString(@order3->id)>', 'association' => 'lineItems']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'orderlineitems', 'id' => '<toString(@order3_line_item1->id)>']
                ]
            ],
            $response
        );
    }

    public function testGetRelationshipForLineItemsOfOrderForChildCustomer(): void
    {
        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order3->id)>', 'association' => 'lineItems']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'orderlineitems', 'id' => '<toString(@order3_line_item1->id)>']
                ]
            ],
            $response
        );
    }

    public function testTryToGetSubresourceForLineItemsOfOrderForCustomerFromAnotherDepartment(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => '<toString(@another_order->id)>', 'association' => 'lineItems'],
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

    public function testTryToGetRelationshipForLineItemsOfOrderForCustomerFromAnotherDepartment(): void
    {
        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => '<toString(@another_order->id)>', 'association' => 'lineItems'],
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

    public function testTryToUpdateRelationshipForLineItems(): void
    {
        $response = $this->patchRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'lineItems'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToAddRelationshipForLineItems(): void
    {
        $response = $this->postRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'lineItems'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteRelationshipForLineItems(): void
    {
        $response = $this->deleteRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'lineItems'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testGetSubresourceForCustomer(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'customer']
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'customers', 'id' => '<toString(@customer->id)>']],
            $response
        );
    }

    public function testGetRelationshipForCustomer(): void
    {
        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'customer']
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'customers', 'id' => '<toString(@customer->id)>']],
            $response
        );
    }

    public function testGetSubresourceForCustomerOfOrderForChildCustomer(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => '<toString(@order3->id)>', 'association' => 'customer']
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'customers', 'id' => '<toString(@customer1->id)>']],
            $response
        );
    }

    public function testGetRelationshipForCustomerOfOrderForChildCustomer(): void
    {
        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order3->id)>', 'association' => 'customer']
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'customers', 'id' => '<toString(@customer1->id)>']],
            $response
        );
    }

    public function testTryToGetSubresourceForCustomerOfOrderForCustomerFromAnotherDepartment(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => '<toString(@another_order->id)>', 'association' => 'customer'],
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

    public function testTryToGetRelationshipForCustomerOfOrderForCustomerFromAnotherDepartment(): void
    {
        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => '<toString(@another_order->id)>', 'association' => 'customer'],
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

    public function testTryToUpdateRelationshipForCustomer(): void
    {
        $response = $this->patchRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'customer'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testGetSubresourceForCustomerUser(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'customerUser']
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'customerusers', 'id' => '<toString(@customer_user->id)>']],
            $response
        );
    }

    public function testGetRelationshipForCustomerUser(): void
    {
        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'customerUser']
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'customerusers', 'id' => '<toString(@customer_user->id)>']],
            $response
        );
    }

    public function testGetSubresourceForCustomerUserOfOrderForChildCustomer(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => '<toString(@order3->id)>', 'association' => 'customerUser']
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'customerusers', 'id' => '<toString(@customer_user1->id)>']],
            $response
        );
    }

    public function testGetRelationshipForCustomerUserOfOrderForChildCustomer(): void
    {
        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order3->id)>', 'association' => 'customerUser']
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'customerusers', 'id' => '<toString(@customer_user1->id)>']],
            $response
        );
    }

    public function testTryToGetSubresourceForCustomerUserOfOrderForCustomerFromAnotherDepartment(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => '<toString(@another_order->id)>', 'association' => 'customerUser'],
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

    public function testTryToGetRelationshipForCustomerUserOfOrderForCustomerFromAnotherDepartment(): void
    {
        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => '<toString(@another_order->id)>', 'association' => 'customerUser'],
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

    public function testTryToUpdateRelationshipForCustomerUser(): void
    {
        $response = $this->patchRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'customerUser'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToGetSubresourceForPaymentTerm(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'paymentTerm'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToGetRelationshipForPaymentTerm(): void
    {
        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'paymentTerm'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToUpdateRelationshipForPaymentTerm(): void
    {
        $response = $this->patchRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'paymentTerm'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testGetSubresourceForBillingAddress(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'billingAddress']
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'orderaddresses', 'id' => '<toString(@order1_billing_address->id)>']],
            $response
        );
    }

    public function testGetRelationshipForBillingAddress(): void
    {
        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'billingAddress']
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'orderaddresses', 'id' => '<toString(@order1_billing_address->id)>']],
            $response
        );
    }

    public function testGetSubresourceForBillingAddressOfOrderForChildCustomer(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => '<toString(@order3->id)>', 'association' => 'billingAddress']
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'orderaddresses', 'id' => '<toString(@order3_billing_address->id)>']],
            $response
        );
    }

    public function testGetRelationshipForBillingAddressOfOrderForChildCustomer(): void
    {
        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order3->id)>', 'association' => 'billingAddress']
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'orderaddresses', 'id' => '<toString(@order3_billing_address->id)>']],
            $response
        );
    }

    public function testTryToGetSubresourceForBillingAddressOfOrderForCustomerFromAnotherDepartment(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => '<toString(@another_order->id)>', 'association' => 'billingAddress'],
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

    public function testTryToGetRelationshipForBillingAddressOfOrderForCustomerFromAnotherDepartment(): void
    {
        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => '<toString(@another_order->id)>', 'association' => 'billingAddress'],
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

    public function testTryToUpdateRelationshipForBillingAddress(): void
    {
        $response = $this->patchRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'billingAddress'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testGetSubresourceForShippingAddress(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'shippingAddress']
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'orderaddresses', 'id' => '<toString(@order1_shipping_address->id)>']],
            $response
        );
    }

    public function testGetRelationshipForShippingAddress(): void
    {
        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'shippingAddress']
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'orderaddresses', 'id' => '<toString(@order1_shipping_address->id)>']],
            $response
        );
    }

    public function testGetSubresourceForShippingAddressOfOrderForChildCustomer(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => '<toString(@order3->id)>', 'association' => 'shippingAddress']
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'orderaddresses', 'id' => '<toString(@order3_shipping_address->id)>']],
            $response
        );
    }

    public function testGetRelationshipForShippingAddressOfOrderForChildCustomer(): void
    {
        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order3->id)>', 'association' => 'shippingAddress']
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'orderaddresses', 'id' => '<toString(@order3_shipping_address->id)>']],
            $response
        );
    }

    public function testTryToGetSubresourceForShippingAddressOfOrderForCustomerFromAnotherDepartment(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => '<toString(@another_order->id)>', 'association' => 'shippingAddress'],
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

    public function testTryToGetRelationshipForShippingAddressOfOrderForCustomerFromAnotherDepartment(): void
    {
        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => '<toString(@another_order->id)>', 'association' => 'shippingAddress'],
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

    public function testTryToUpdateRelationshipForShippingAddress(): void
    {
        $response = $this->patchRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'shippingAddress'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testGetSubresourceForStatus(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'status']
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'orderstatuses', 'id' => 'archived']],
            $response
        );
    }

    public function testGetRelationshipForStatus(): void
    {
        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'status']
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'orderstatuses', 'id' => 'archived']],
            $response
        );
    }

    public function testTryToUpdateRelationshipForStatus(): void
    {
        $response = $this->patchRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'status'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }
}
