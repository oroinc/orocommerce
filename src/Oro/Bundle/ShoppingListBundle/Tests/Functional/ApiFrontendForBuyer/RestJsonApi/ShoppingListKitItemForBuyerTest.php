<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\ApiFrontendForBuyer\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadBuyerCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ProductKitItemLineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingListTotal;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListTotalManager;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 */
class ShoppingListKitItemForBuyerTest extends FrontendRestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadBuyerCustomerUserData::class,
            '@OroShoppingListBundle/Tests/Functional/ApiFrontend/DataFixtures/shopping_list.yml',
        ]);

        /** @var ShoppingListTotalManager $totalManager */
        $totalManager = self::getContainer()->get('oro_shopping_list.manager.shopping_list_total');
        for ($i = 1; $i <= 5; $i++) {
            $totalManager->recalculateTotals(
                $this->getReference(sprintf('shopping_list%d', $i)),
                true
            );
        }
    }

    protected function getRequestDataFolderName(): string
    {
        return '../../ApiFrontend/RestJsonApi/requests';
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'shoppinglistkititems'], [], ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains('cget_kit_line_item_buyer.yml', $response);
        self::assertEquals(2, $response->headers->get('X-Include-Total-Count'));
    }

    public function testGet(): void
    {
        $response = $this->get(
            ['entity' => 'shoppinglistkititems', 'id' => '<toString(@product_kit_item1_line_item1->id)>']
        );

        $this->assertResponseContains(
            '@OroShoppingListBundle/Tests/Functional/ApiFrontend/RestJsonApi/responses/get_kit_item_line_item.yml',
            $response
        );
    }

    /**
     * @dataProvider getAccessDeniedDataProvider
     */
    public function testGetAccessDenied(string $productKitItemLineItemId): void
    {
        $response = $this->get(
            ['entity' => 'shoppinglistkititems', 'id' => $productKitItemLineItemId],
            [],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'No access to the entity.',
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function getAccessDeniedDataProvider(): array
    {
        return [
            'another customer user' => [
                'productKitItemLineItemId' => '<toString(@product_kit_item2_line_item1->id)>',
            ],
            'another website' => [
                'productKitItemLineItemId' => '<toString(@product_kit_item3_line_item1->id)>',
            ],
            'another customer' => [
                'productKitItemLineItemId' => '<toString(@product_kit_item4_line_item1->id)>',
            ],
        ];
    }

    public function testUpdate(): void
    {
        $shoppingList1 = $this->getReference('shopping_list1');
        $this->assertShoppingListTotal($shoppingList1, 59.15, 'USD');

        $kitItemLineItemId = (string) $this->getReference('product_kit_item1_line_item1')->getId();
        $data = [
            'data' => [
                'type' => 'shoppinglistkititems',
                'id' => $kitItemLineItemId,
                'attributes' => [
                    'quantity' => 123.45
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'shoppinglistkititems', 'id' => $kitItemLineItemId],
            $data
        );
        $this->assertResponseContains($data, $response);

        /** @var ProductKitItemLineItem $kitItemLineItem */
        $kitItemLineItem = $this->getEntityManager()
            ->getRepository(ProductKitItemLineItem::class)
            ->find($kitItemLineItemId);
        self::assertNotNull($kitItemLineItem);
        self::assertEquals(123.45, $kitItemLineItem->getQuantity());

        $kitItemLineItemShoppingList = $kitItemLineItem->getLineItem()->getShoppingList();
        self::assertEquals($shoppingList1->getId(), $kitItemLineItemShoppingList->getId());
        $this->assertShoppingListTotal($kitItemLineItemShoppingList, 303.59, 'USD');
    }

    /**
     * @dataProvider getAccessDeniedDataProvider
     */
    public function testUpdateAccessDenied(string $productKitItemLineItemId): void
    {
        $data = [
            'data' => [
                'type' => 'shoppinglistkititems',
                'id' => $productKitItemLineItemId,
                'attributes' => [
                    'quantity' => 123.45
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'shoppinglistkititems', 'id' => $productKitItemLineItemId],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'No access to the entity.',
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testCreate(): void
    {
        $shoppingList1 = $this->getReference('shopping_list1');
        $this->assertShoppingListTotal($shoppingList1, 59.15, 'USD');

        /** @var LineItem $lineItem */
        $lineItem = $this->getReference('kit_line_item1');
        $productKitItemId = $this->getReference('product_kit1_item3')->getId();
        $productId = $this->getReference('product4')->getId();
        $productUnitCode = $this->getReference('item')->getCode();

        self::assertCount(2, $lineItem->getKitItemLineItems());

        $response = $this->post(
            ['entity' => 'shoppinglistkititems'],
            'create_kit_item_line_item.yml'
        );

        $kitItemLineItemId = (int)$this->getResourceId($response);
        $responseContent = $this->updateResponseContent(
            '@OroShoppingListBundle/Tests/Functional/ApiFrontend/RestJsonApi/responses/create_kit_item_line_item.yml',
            $response
        );
        $this->assertResponseContains($responseContent, $response);

        /** @var ProductKitItemLineItem $kitItemLineItem */
        $kitItemLineItem = $this->getEntityManager()
            ->getRepository(ProductKitItemLineItem::class)
            ->find($kitItemLineItemId);
        self::assertNotNull($kitItemLineItem, 'ProductKitItemLineItem is not found');
        self::assertProductKitItemLineItem(
            $kitItemLineItem,
            $lineItem->getId(),
            $productKitItemId,
            $productId,
            $productUnitCode,
            5,
            1
        );
        self::assertCount(3, $kitItemLineItem->getLineItem()->getKitItemLineItems());

        $kitItemLineItemShoppingList = $kitItemLineItem->getLineItem()->getShoppingList();
        self::assertEquals($shoppingList1->getId(), $kitItemLineItemShoppingList->getId());
        $this->assertShoppingListTotal($kitItemLineItemShoppingList, 91.25, 'USD');
    }

    /**
     * @dataProvider getAccessDeniedDataProvider
     */
    public function testDeleteAccessDenied(string $productKitItemLineItemId): void
    {
        $response = $this->delete(
            ['entity' => 'shoppinglistkititems', 'id' => $productKitItemLineItemId],
            [],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'No access to the entity.',
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testDelete(): void
    {
        /** @var ProductKitItemLineItem $kitItemLineItemReference */
        $kitItemLineItemReference = $this->getReference('product_kit_item1_line_item1');
        $kitItemLineItemId = $kitItemLineItemReference->getId();
        $lineItemId = $kitItemLineItemReference->getLineItem()->getId();
        $shoppingList1 = $this->getReference('shopping_list1');
        $this->assertShoppingListTotal($shoppingList1, 59.15, 'USD');

        $this->delete(
            ['entity' => 'shoppinglistkititems', 'id' => (string)$kitItemLineItemId]
        );

        /** @var ProductKitItemLineItem $kitItemLineItem */
        $kitItemLineItem = $this->getEntityManager()
            ->getRepository(ProductKitItemLineItem::class)
            ->find($kitItemLineItemId);
        self::assertNull($kitItemLineItem);

        /** @var LineItem $lineItem */
        $lineItem = $this->getEntityManager()
            ->getRepository(LineItem::class)
            ->find($lineItemId);
        self::assertNotNull($lineItem);
        self::assertCount(1, $lineItem->getKitItemLineItems());

        $lineItemShoppingList = $lineItem->getShoppingList();
        self::assertEquals($shoppingList1->getId(), $lineItemShoppingList->getId());
        $this->assertShoppingListTotal($lineItemShoppingList, 54.23, 'USD');
    }

    private static function assertProductKitItemLineItem(
        ProductKitItemLineItem $kitItemLineItem,
        int $lineItemId,
        int $productKitItemId,
        int $productId,
        string $productUnitCode,
        float $quantity,
        int $sortOrder
    ): void {
        self::assertEquals($lineItemId, $kitItemLineItem->getLineItem()->getId());
        self::assertEquals($productKitItemId, $kitItemLineItem->getKitItem()->getId());
        self::assertEquals($productId, $kitItemLineItem->getProduct()->getId());
        self::assertEquals($productUnitCode, $kitItemLineItem->getProductUnitCode());
        self::assertEquals($quantity, $kitItemLineItem->getQuantity());
        self::assertEquals($sortOrder, $kitItemLineItem->getSortOrder());
    }

    private function assertShoppingListTotal(
        ShoppingList $shoppingList,
        float $total,
        string $currency
    ): void {
        /** @var ShoppingListTotal[] $totals */
        $totals = $this->getEntityManager()
            ->getRepository(ShoppingListTotal::class)
            ->findBy(['shoppingList' => $shoppingList]);
        self::assertCount(1, $totals);
        $totalEntity = $totals[0];
        self::assertEquals($total, $totalEntity->getSubtotal()->getAmount());
        self::assertEquals($currency, $totalEntity->getCurrency());
    }
}
