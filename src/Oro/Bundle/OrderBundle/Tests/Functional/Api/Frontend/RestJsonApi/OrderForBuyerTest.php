<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadBuyerCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\Api\FrontendRestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class OrderForBuyerTest extends FrontendRestJsonApiTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures([
            LoadBuyerCustomerUserData::class,
            '@OroOrderBundle/Tests/Functional/Api/Frontend/DataFixtures/orders.yml'
        ]);
    }

    public function testGetList()
    {
        $response = $this->cget(['entity' => 'orders']);

        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'orders', 'id' => '<toString(@order1->id)>'],
                    ['type' => 'orders', 'id' => '<toString(@order2->id)>']
                ]
            ],
            $response
        );
    }

    public function testGet()
    {
        $response = $this->get(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>']
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'orders', 'id' => '<toString(@order1->id)>']],
            $response
        );
    }

    public function testGetForChildCustomer()
    {
        $response = $this->get(
            ['entity' => 'orders', 'id' => '<toString(@order3->id)>'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
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
        $this->assertResponseContains(['data' => []], $response);
    }

    public function testGetRelationshipForLineItemsOfOrderForChildCustomer()
    {
        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order3->id)>', 'association' => 'lineItems']
        );
        $this->assertResponseContains(['data' => []], $response);
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
        $this->assertResponseContains(['data' => null], $response);
    }

    public function testGetRelationshipForCustomerOfOrderForChildCustomer()
    {
        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order3->id)>', 'association' => 'customer']
        );
        $this->assertResponseContains(['data' => null], $response);
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
        $this->assertResponseContains(['data' => null], $response);
    }

    public function testGetRelationshipForCustomerUserOfOrderForChildCustomer()
    {
        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order3->id)>', 'association' => 'customerUser']
        );
        $this->assertResponseContains(['data' => null], $response);
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
        $this->assertResponseContains(['data' => null], $response);
    }

    public function testGetRelationshipForBillingAddressOfOrderForChildCustomer()
    {
        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order3->id)>', 'association' => 'billingAddress']
        );
        $this->assertResponseContains(['data' => null], $response);
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
        $this->assertResponseContains(['data' => null], $response);
    }

    public function testGetRelationshipForShippingAddressOfOrderForChildCustomer()
    {
        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order3->id)>', 'association' => 'shippingAddress']
        );
        $this->assertResponseContains(['data' => null], $response);
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
