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
    protected function setUp(): void
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
        $this->assertResponseValidationError(
            [
                'title'  => 'access denied exception',
                'detail' => 'No access to the entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
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
        $this->assertResponseValidationError(
            [
                'title'  => 'access denied exception',
                'detail' => 'No access to the entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
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
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET, POST');
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

    public function testTryToGetSubresourceForLineItemsOfOrderForChildCustomer()
    {
        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => '<toString(@order3->id)>', 'association' => 'lineItems'],
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

    public function testTryToGetRelationshipForLineItemsOfOrderForChildCustomer()
    {
        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order3->id)>', 'association' => 'lineItems'],
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

    public function testTryToGetSubresourceForLineItemsOfOrderForCustomerFromAnotherDepartment()
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

    public function testTryToGetRelationshipForLineItemsOfOrderForCustomerFromAnotherDepartment()
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

    public function testTryToGetSubresourceForCustomerOfOrderForChildCustomer()
    {
        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => '<toString(@order3->id)>', 'association' => 'customer'],
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

    public function testTryToGetRelationshipForCustomerOfOrderForChildCustomer()
    {
        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order3->id)>', 'association' => 'customer'],
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

    public function testTryToGetSubresourceForCustomerOfOrderForCustomerFromAnotherDepartment()
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

    public function testTryToGetRelationshipForCustomerOfOrderForCustomerFromAnotherDepartment()
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

    public function testTryToGetSubresourceForCustomerUserOfOrderForChildCustomer()
    {
        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => '<toString(@order3->id)>', 'association' => 'customerUser'],
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

    public function testTryToGetRelationshipForCustomerUserOfOrderForChildCustomer()
    {
        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order3->id)>', 'association' => 'customerUser'],
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

    public function testTryToGetSubresourceForCustomerUserOfOrderForCustomerFromAnotherDepartment()
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

    public function testTryToGetRelationshipForCustomerUserOfOrderForCustomerFromAnotherDepartment()
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

    public function testTryToGetSubresourceForBillingAddressOfOrderForChildCustomer()
    {
        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => '<toString(@order3->id)>', 'association' => 'billingAddress'],
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

    public function testTryToGetRelationshipForBillingAddressOfOrderForChildCustomer()
    {
        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order3->id)>', 'association' => 'billingAddress'],
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

    public function testTryToGetSubresourceForBillingAddressOfOrderForCustomerFromAnotherDepartment()
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

    public function testTryToGetRelationshipForBillingAddressOfOrderForCustomerFromAnotherDepartment()
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

    public function testTryToGetSubresourceForShippingAddressOfOrderForChildCustomer()
    {
        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => '<toString(@order3->id)>', 'association' => 'shippingAddress'],
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

    public function testTryToGetRelationshipForShippingAddressOfOrderForChildCustomer()
    {
        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order3->id)>', 'association' => 'shippingAddress'],
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

    public function testTryToGetSubresourceForShippingAddressOfOrderForCustomerFromAnotherDepartment()
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

    public function testTryToGetRelationshipForShippingAddressOfOrderForCustomerFromAnotherDepartment()
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
