<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\Api\FrontendRestJsonApiTestCase;
use Oro\Bundle\OrderBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadPaymentOrderStatuses;
use Oro\Bundle\OrderBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadPaymentTransactions;
use Oro\Bundle\OrderBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadShippingMethods;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class OrderTest extends FrontendRestJsonApiTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            '@OroOrderBundle/Tests/Functional/Api/Frontend/DataFixtures/orders.yml',
            LoadShippingMethods::class,
            LoadPaymentOrderStatuses::class,
            LoadPaymentTransactions::class
        ]);
    }

    public function testGetList()
    {
        $response = $this->cget(['entity' => 'orders']);

        $this->assertResponseContains('cget_order.yml', $response);
    }

    public function testGet()
    {
        $response = $this->get(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>']
        );

        $this->assertResponseContains('get_order.yml', $response);
    }

    public function testGetForChildCustomer()
    {
        $response = $this->get(
            ['entity' => 'orders', 'id' => '<toString(@order3->id)>']
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'orders', 'id' => '<toString(@order3->id)>']],
            $response
        );
    }

    public function testTryToGetForCustomerFromAnotherDepartment()
    {
        $response = $this->get(
            ['entity' => 'orders', 'id' => '<toString(@another_order->id)>'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testGetShouldReturnCorrectShippingMethodAmountEvenIfOtherFieldsWereNotRequested()
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

    public function testGetShouldReturnCorrectShippingCostAmountEvenIfOtherFieldsWereNotRequested()
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

    public function testTryToCreate()
    {
        $response = $this->post(
            ['entity' => 'orders'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToUpdate()
    {
        $response = $this->patch(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDelete()
    {
        $response = $this->delete(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteList()
    {
        $response = $this->cdelete(
            ['entity' => 'orders'],
            ['filter' => ['id' => '<toString(@order1->id)>']],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testGetSubresourceForLineItems()
    {
        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'lineItems']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'orderlineitems', 'id' => '<toString(@order1_line_item1->id)>'],
                    ['type' => 'orderlineitems', 'id' => '<toString(@order1_line_item2->id)>']
                ]
            ],
            $response
        );
    }

    public function testGetRelationshipForLineItems()
    {
        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'lineItems']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'orderlineitems', 'id' => '<toString(@order1_line_item1->id)>'],
                    ['type' => 'orderlineitems', 'id' => '<toString(@order1_line_item2->id)>']
                ]
            ],
            $response
        );
    }

    public function testGetSubresourceForLineItemsOfOrderForChildCustomer()
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

    public function testGetRelationshipForLineItemsOfOrderForChildCustomer()
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

    public function testTryToGetSubresourceForLineItemsOfOrderForCustomerFromAnotherDepartment()
    {
        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => '<toString(@another_order->id)>', 'association' => 'lineItems']
        );
        $this->assertResponseContains(['data' => []], $response);
    }

    public function testTryToGetRelationshipForLineItemsOfOrderForCustomerFromAnotherDepartment()
    {
        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => '<toString(@another_order->id)>', 'association' => 'lineItems']
        );
        $this->assertResponseContains(['data' => []], $response);
    }

    public function testTryToUpdateRelationshipForLineItems()
    {
        $response = $this->patchRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'lineItems'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToAddRelationshipForLineItems()
    {
        $response = $this->postRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'lineItems'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteRelationshipForLineItems()
    {
        $response = $this->deleteRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'lineItems'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testGetSubresourceForCustomer()
    {
        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'customer']
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'customers', 'id' => '<toString(@customer->id)>']],
            $response
        );
    }

    public function testGetRelationshipForCustomer()
    {
        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'customer']
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'customers', 'id' => '<toString(@customer->id)>']],
            $response
        );
    }

    public function testGetSubresourceForCustomerOfOrderForChildCustomer()
    {
        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => '<toString(@order3->id)>', 'association' => 'customer']
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'customers', 'id' => '<toString(@customer1->id)>']],
            $response
        );
    }

    public function testGetRelationshipForCustomerOfOrderForChildCustomer()
    {
        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order3->id)>', 'association' => 'customer']
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'customers', 'id' => '<toString(@customer1->id)>']],
            $response
        );
    }

    public function testTryToGetSubresourceForCustomerOfOrderForCustomerFromAnotherDepartment()
    {
        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => '<toString(@another_order->id)>', 'association' => 'customer']
        );
        $this->assertResponseContains(['data' => null], $response);
    }

    public function testTryToGetRelationshipForCustomerOfOrderForCustomerFromAnotherDepartment()
    {
        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => '<toString(@another_order->id)>', 'association' => 'customer']
        );
        $this->assertResponseContains(['data' => null], $response);
    }

    public function testTryToUpdateRelationshipForCustomer()
    {
        $response = $this->patchRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'customer'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testGetSubresourceForCustomerUser()
    {
        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'customerUser']
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'customerusers', 'id' => '<toString(@customer_user->id)>']],
            $response
        );
    }

    public function testGetRelationshipForCustomerUser()
    {
        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'customerUser']
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'customerusers', 'id' => '<toString(@customer_user->id)>']],
            $response
        );
    }

    public function testGetSubresourceForCustomerUserOfOrderForChildCustomer()
    {
        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => '<toString(@order3->id)>', 'association' => 'customerUser']
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'customerusers', 'id' => '<toString(@customer_user1->id)>']],
            $response
        );
    }

    public function testGetRelationshipForCustomerUserOfOrderForChildCustomer()
    {
        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order3->id)>', 'association' => 'customerUser']
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'customerusers', 'id' => '<toString(@customer_user1->id)>']],
            $response
        );
    }

    public function testTryToGetSubresourceForCustomerUserOfOrderForCustomerFromAnotherDepartment()
    {
        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => '<toString(@another_order->id)>', 'association' => 'customerUser']
        );
        $this->assertResponseContains(['data' => null], $response);
    }

    public function testTryToGetRelationshipForCustomerUserOfOrderForCustomerFromAnotherDepartment()
    {
        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => '<toString(@another_order->id)>', 'association' => 'customerUser']
        );
        $this->assertResponseContains(['data' => null], $response);
    }

    public function testTryToUpdateRelationshipForCustomerUser()
    {
        $response = $this->patchRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'customerUser'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToGetSubresourceForPaymentTerm()
    {
        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'paymentTerm'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToGetRelationshipForPaymentTerm()
    {
        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'paymentTerm'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToUpdateRelationshipForPaymentTerm()
    {
        $response = $this->patchRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'paymentTerm'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testGetSubresourceForBillingAddress()
    {
        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'billingAddress']
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'orderaddresses', 'id' => '<toString(@order1_billing_address->id)>']],
            $response
        );
    }

    public function testGetRelationshipForBillingAddress()
    {
        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'billingAddress']
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'orderaddresses', 'id' => '<toString(@order1_billing_address->id)>']],
            $response
        );
    }

    public function testGetSubresourceForBillingAddressOfOrderForChildCustomer()
    {
        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => '<toString(@order3->id)>', 'association' => 'billingAddress']
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'orderaddresses', 'id' => '<toString(@order3_billing_address->id)>']],
            $response
        );
    }

    public function testGetRelationshipForBillingAddressOfOrderForChildCustomer()
    {
        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order3->id)>', 'association' => 'billingAddress']
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'orderaddresses', 'id' => '<toString(@order3_billing_address->id)>']],
            $response
        );
    }

    public function testTryToGetSubresourceForBillingAddressOfOrderForCustomerFromAnotherDepartment()
    {
        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => '<toString(@another_order->id)>', 'association' => 'billingAddress']
        );
        $this->assertResponseContains(['data' => null], $response);
    }

    public function testTryToGetRelationshipForBillingAddressOfOrderForCustomerFromAnotherDepartment()
    {
        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => '<toString(@another_order->id)>', 'association' => 'billingAddress']
        );
        $this->assertResponseContains(['data' => null], $response);
    }

    public function testTryToUpdateRelationshipForBillingAddress()
    {
        $response = $this->patchRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'billingAddress'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testGetSubresourceForShippingAddress()
    {
        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'shippingAddress']
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'orderaddresses', 'id' => '<toString(@order1_shipping_address->id)>']],
            $response
        );
    }

    public function testGetRelationshipForShippingAddress()
    {
        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'shippingAddress']
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'orderaddresses', 'id' => '<toString(@order1_shipping_address->id)>']],
            $response
        );
    }

    public function testGetSubresourceForShippingAddressOfOrderForChildCustomer()
    {
        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => '<toString(@order3->id)>', 'association' => 'shippingAddress']
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'orderaddresses', 'id' => '<toString(@order3_shipping_address->id)>']],
            $response
        );
    }

    public function testGetRelationshipForShippingAddressOfOrderForChildCustomer()
    {
        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order3->id)>', 'association' => 'shippingAddress']
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'orderaddresses', 'id' => '<toString(@order3_shipping_address->id)>']],
            $response
        );
    }

    public function testTryToGetSubresourceForShippingAddressOfOrderForCustomerFromAnotherDepartment()
    {
        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => '<toString(@another_order->id)>', 'association' => 'shippingAddress']
        );
        $this->assertResponseContains(['data' => null], $response);
    }

    public function testTryToGetRelationshipForShippingAddressOfOrderForCustomerFromAnotherDepartment()
    {
        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => '<toString(@another_order->id)>', 'association' => 'shippingAddress']
        );
        $this->assertResponseContains(['data' => null], $response);
    }

    public function testTryToUpdateRelationshipForShippingAddress()
    {
        $response = $this->patchRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'shippingAddress'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }
}
