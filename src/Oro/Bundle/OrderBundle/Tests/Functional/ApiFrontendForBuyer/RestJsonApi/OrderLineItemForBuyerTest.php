<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\ApiFrontendForBuyer\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadBuyerCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class OrderLineItemForBuyerTest extends FrontendRestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadBuyerCustomerUserData::class,
            '@OroOrderBundle/Tests/Functional/ApiFrontend/DataFixtures/orders.yml'
        ]);
    }

    protected function getResponseDataFolderName(): string
    {
        return '../../ApiFrontend/RestJsonApi/responses';
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'orderlineitems']);

        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'orderlineitems', 'id' => '<toString(@order1_line_item1->id)>'],
                    ['type' => 'orderlineitems', 'id' => '<toString(@order1_line_item2->id)>'],
                    ['type' => 'orderlineitems', 'id' => '<toString(@order2_line_item1->id)>'],
                    ['type' => 'orderlineitems', 'id' => '<toString(@product_kit_2_line_item.1->id)>'],
                    ['type' => 'orderlineitems', 'id' => '<toString(@product_kit_3_line_item.1->id)>'],
                    ['type' => 'orderlineitems', 'id' => '<toString(@product_kit_2_line_item.2->id)>'],
                ]
            ],
            $response
        );
    }

    public function testGet(): void
    {
        $response = $this->get(
            ['entity' => 'orderlineitems', 'id' => '<toString(@order1_line_item1->id)>']
        );

        $this->assertResponseContains('get_line_item.yml', $response);
    }

    public function testGetProductKitLineItem(): void
    {
        $response = $this->get(
            ['entity' => 'orderlineitems', 'id' => '<toString(@product_kit_2_line_item.1->id)>']
        );

        $this->assertResponseContains('get_product_kit_line_item.yml', $response);
    }

    /**
     * @dataProvider getTryToGetForChildCustomerDataProvider
     */
    public function testTryToGetForChildCustomer(string $lineItemReference): void
    {
        $response = $this->get(
            ['entity' => 'orderlineitems', 'id' => $lineItemReference],
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

    public function getTryToGetForChildCustomerDataProvider(): array
    {
        return [
            'line item' => [
                'lineItemReference' => '<toString(@order3_line_item1->id)>',
            ],
            'product kit line item' => [
                'lineItemReference' => '<toString(@order5_product_kit_2_line_item.1->id)>',
            ],
        ];
    }

    public function testTryToGetForCustomerFromAnotherDepartment(): void
    {
        $response = $this->get(
            ['entity' => 'orderlineitems', 'id' => '<toString(@another_order_line_item1->id)>'],
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

    public function testTryToCreate(): void
    {
        $response = $this->post(
            ['entity' => 'orderlineitems'],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'access denied exception',
                'detail' => 'Use API resource to create an order.'
                    . ' An order line item can be created only together with an order.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToUpdate(): void
    {
        $response = $this->patch(
            ['entity' => 'orderlineitems', 'id' => '<toString(@order1_line_item1->id)>'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDelete(): void
    {
        $response = $this->delete(
            ['entity' => 'orderlineitems', 'id' => '<toString(@order1_line_item1->id)>'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteList(): void
    {
        $response = $this->cdelete(
            ['entity' => 'orderlineitems'],
            ['filter' => ['id' => '<toString(@order1_line_item1->id)>']],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET, POST');
    }

    public function testGetSubresourceForOrder(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'orderlineitems', 'id' => '<toString(@order1_line_item1->id)>', 'association' => 'order']
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'orders', 'id' => '<toString(@order1->id)>']],
            $response
        );
    }

    public function testGetRelationshipForOrder(): void
    {
        $response = $this->getRelationship(
            ['entity' => 'orderlineitems', 'id' => '<toString(@order1_line_item1->id)>', 'association' => 'order']
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'orders', 'id' => '<toString(@order1->id)>']],
            $response
        );
    }

    public function testTryToGetSubresourceForOrderForChildCustomer(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'orderlineitems', 'id' => '<toString(@order3_line_item1->id)>', 'association' => 'order'],
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

    public function testTryToGetRelationshipForOrderForChildCustomer(): void
    {
        $response = $this->getRelationship(
            ['entity' => 'orderlineitems', 'id' => '<toString(@order3_line_item1->id)>', 'association' => 'order'],
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

    public function testTryToGetSubresourceForOrderForCustomerFromAnotherDepartment(): void
    {
        $response = $this->getSubresource(
            [
                'entity'      => 'orderlineitems',
                'id'          => '<toString(@another_order_line_item1->id)>',
                'association' => 'order'
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

    public function testTryToGetRelationshipForOrderForCustomerFromAnotherDepartment(): void
    {
        $response = $this->getRelationship(
            [
                'entity'      => 'orderlineitems',
                'id'          => '<toString(@another_order_line_item1->id)>',
                'association' => 'order'
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

    public function testTryToUpdateRelationshipForOrder(): void
    {
        $response = $this->patchRelationship(
            ['entity' => 'orderlineitems', 'id' => '<toString(@order1_line_item1->id)>', 'association' => 'order'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testGetSubresourceForKitItemLineItems(): void
    {
        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference('product_kit_2_line_item.1');
        $kitItemLineItemsData = [];
        foreach ($lineItem->getKitItemLineItems() as $kitItemLineItem) {
            $kitItemLineItemsData[] = [
                'type' => 'orderproductkititemlineitems',
                'id' => (string)$kitItemLineItem->getId(),
            ];
        }

        $response = $this->getSubresource(
            ['entity' => 'orderlineitems', 'id' => (string)$lineItem->getId(), 'association' => 'kitItemLineItems']
        );

        $this->assertResponseContains(['data' => $kitItemLineItemsData], $response);
    }

    public function testGetRelationshipForKitItemLineItems(): void
    {
        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference('product_kit_2_line_item.1');
        $kitItemLineItemsData = [];
        foreach ($lineItem->getKitItemLineItems() as $kitItemLineItem) {
            $kitItemLineItemsData[] = [
                'type' => 'orderproductkititemlineitems',
                'id' => (string)$kitItemLineItem->getId(),
            ];
        }

        $response = $this->getRelationship(
            ['entity' => 'orderlineitems', 'id' => (string)$lineItem->getId(), 'association' => 'kitItemLineItems']
        );

        $this->assertResponseContains(['data' => $kitItemLineItemsData], $response);
    }

    public function testTryToUpdateRelationshipForKitItemLineItems(): void
    {
        $response = $this->patchRelationship(
            [
                'entity' => 'orderlineitems',
                'id' => '<toString(@product_kit_2_line_item.1->id)>',
                'association' => 'kitItemLineItems',
            ],
            [],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToAddRelationshipForKitItemLineItems(): void
    {
        $response = $this->postRelationship(
            [
                'entity' => 'orderlineitems',
                'id' => '<toString(@product_kit_2_line_item.1->id)>',
                'association' => 'kitItemLineItems',
            ],
            [
                'data' => [],
            ],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteRelationshipForKitItemLineItems(): void
    {
        $response = $this->deleteRelationship(
            [
                'entity' => 'orderlineitems',
                'id' => '<toString(@product_kit_2_line_item.1->id)>',
                'association' => 'kitItemLineItems',
            ],
            [
                'data' => [
                    [
                        'type' => 'orderproductkititemlineitems',
                        'id' => '<toString(@order_product_kit_2_line_item.1_kit_item_line_item.1->id)>',
                    ],
                ],
            ],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }
}
