<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\ApiFrontendForBuyer\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadBuyerCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class OrderForBuyerTest extends FrontendRestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadBuyerCustomerUserData::class,
            '@OroOrderBundle/Tests/Functional/ApiFrontend/DataFixtures/orders.yml'
        ]);
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'orders']);

        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'orders', 'id' => '<toString(@order1->id)>'],
                    ['type' => 'orders', 'id' => '<toString(@order2->id)>'],
                    ['type' => 'orders', 'id' => '<toString(@order4->id)>'],
                ]
            ],
            $response
        );
    }

    /**
     * @dataProvider getOrderDataProvider
     */
    public function testGet(string $orderReference): void
    {
        $response = $this->get(
            ['entity' => 'orders', 'id' => $orderReference]
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'orders', 'id' => $orderReference]],
            $response
        );
    }

    public function getOrderDataProvider(): array
    {
        return [
            'order' => [
                'orderReference' => '<toString(@order1->id)>',
            ],
            'order with product kit line items' => [
                'orderReference' => '<toString(@order4->id)>',
            ],
        ];
    }

    public function testGetForChildCustomer(): void
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

    public function testTryToGetSubresourceForLineItemsOfOrderForChildCustomer(): void
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

    public function testTryToGetRelationshipForLineItemsOfOrderForChildCustomer(): void
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

    public function testTryToGetSubresourceForCustomerOfOrderForChildCustomer(): void
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

    public function testTryToGetRelationshipForCustomerOfOrderForChildCustomer(): void
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

    public function testTryToGetSubresourceForCustomerUserOfOrderForChildCustomer(): void
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

    public function testTryToGetRelationshipForCustomerUserOfOrderForChildCustomer(): void
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

    public function testTryToGetSubresourceForBillingAddressOfOrderForChildCustomer(): void
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

    public function testTryToGetRelationshipForBillingAddressOfOrderForChildCustomer(): void
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

    public function testTryToGetSubresourceForShippingAddressOfOrderForChildCustomer(): void
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

    public function testTryToGetRelationshipForShippingAddressOfOrderForChildCustomer(): void
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
}
