<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class OrderLineItemTest extends FrontendRestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            '@OroOrderBundle/Tests/Functional/ApiFrontend/DataFixtures/orders.yml'
        ]);
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'orderlineitems']);
        $this->assertResponseContains('cget_line_item.yml', $response);
    }

    public function testGetListFilteredByOrder(): void
    {
        $response = $this->cget(
            ['entity' => 'orderlineitems'],
            ['filter[orders]' => '<toString(@order1->id)>']
        );
        $this->assertResponseContains('cget_line_item_filter_by_order.yml', $response);
    }

    public function testGetListCheckThatFilteringByCreatedAtIsSupported(): void
    {
        $response = $this->cget(
            ['entity' => 'orderlineitems'],
            [
                'filter[createdAt]' => '@order1_line_item1->createdAt->format("Y-m-d\TH:i:s\Z")',
                'filter[id]' => '<toString(@order1_line_item1->id)>'
            ]
        );
        $this->assertResponseContains(
            ['data' => [['type' => 'orderlineitems', 'id' => '<toString(@order1_line_item1->id)>']]],
            $response
        );
    }

    public function testGetListCheckThatFilteringByUpdatedAtIsSupported(): void
    {
        $response = $this->cget(
            ['entity' => 'orderlineitems'],
            [
                'filter[updatedAt]' => '@order1_line_item1->updatedAt->format("Y-m-d\TH:i:s\Z")',
                'filter[id]' => '<toString(@order1_line_item1->id)>'
            ]
        );
        $this->assertResponseContains(
            ['data' => [['type' => 'orderlineitems', 'id' => '<toString(@order1_line_item1->id)>']]],
            $response
        );
    }

    public function testGetListCheckThatSortingByCreatedAtIsSupported(): void
    {
        $response = $this->cget(
            ['entity' => 'orderlineitems'],
            ['sort' => '-createdAt', 'filter[orders]' => '<toString(@order1->id)>']
        );
        $responseData = self::jsonToArray($response->getContent());
        self::assertCount(2, $responseData['data']);
    }

    public function testGetListCheckThatSortingByUpdatedAtIsSupported(): void
    {
        $response = $this->cget(
            ['entity' => 'orderlineitems'],
            ['sort' => '-updatedAt', 'filter[orders]' => '<toString(@order1->id)>']
        );
        $responseData = self::jsonToArray($response->getContent());
        self::assertCount(2, $responseData['data']);
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
     * @dataProvider getForChildCustomerDataProvider
     */
    public function testGetForChildCustomer(string $lineItemReference): void
    {
        $response = $this->get(
            ['entity' => 'orderlineitems', 'id' => $lineItemReference]
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'orderlineitems', 'id' => $lineItemReference]],
            $response
        );
    }

    public function getForChildCustomerDataProvider(): array
    {
        return [
            'line item' => [
                'lineItemReference' => '<toString(@order3_line_item1->id)>',
            ],
            'product kit line item' => [
                'lineItemReference' => '<toString(@order5_product_kit_2_line_item.1->id)>'
            ]
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
            ['entity' => 'orderlineitems', 'id' => '<toString(@order1_line_item1->id)>', 'association' => 'orders']
        );
        $this->assertResponseContains(
            ['data' => [['type' => 'orders', 'id' => '<toString(@order1->id)>']]],
            $response
        );
    }

    public function testGetRelationshipForOrder(): void
    {
        $response = $this->getRelationship(
            ['entity' => 'orderlineitems', 'id' => '<toString(@order1_line_item1->id)>', 'association' => 'orders']
        );
        $this->assertResponseContains(
            ['data' => [['type' => 'orders', 'id' => '<toString(@order1->id)>']]],
            $response
        );
    }

    public function testGetSubresourceForOrderForChildCustomer(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'orderlineitems', 'id' => '<toString(@order3_line_item1->id)>', 'association' => 'orders']
        );
        $this->assertResponseContains(
            ['data' => [['type' => 'orders', 'id' => '<toString(@order3->id)>']]],
            $response
        );
    }

    public function testGetRelationshipForOrderForChildCustomer(): void
    {
        $response = $this->getRelationship(
            ['entity' => 'orderlineitems', 'id' => '<toString(@order3_line_item1->id)>', 'association' => 'orders']
        );
        $this->assertResponseContains(
            ['data' => [['type' => 'orders', 'id' => '<toString(@order3->id)>']]],
            $response
        );
    }

    public function testTryToGetSubresourceForOrderForCustomerFromAnotherDepartment(): void
    {
        $response = $this->getSubresource(
            [
                'entity'      => 'orderlineitems',
                'id'          => '<toString(@another_order_line_item1->id)>',
                'association' => 'orders'
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
                'association' => 'orders'
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
            ['entity' => 'orderlineitems', 'id' => '<toString(@order1_line_item1->id)>', 'association' => 'orders'],
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
                'id' => (string)$kitItemLineItem->getId()
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
                'id' => (string)$kitItemLineItem->getId()
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
                'association' => 'kitItemLineItems'
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
                'association' => 'kitItemLineItems'
            ],
            [
                'data' => []
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
                'association' => 'kitItemLineItems'
            ],
            [
                'data' => [
                    [
                        'type' => 'orderproductkititemlineitems',
                        'id' => '<toString(@order_product_kit_2_line_item.1_kit_item_line_item.1->id)>'
                    ]
                ]
            ],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }
}
