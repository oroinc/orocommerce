<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\ApiForOrderLineItem\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Oro\Bundle\OrderBundle\Total\TotalHelper;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class OrderProductKitItemLineItemTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroOrderBundle/Tests/Functional/DataFixtures/order_product_kit_line_items.yml'
        ]);

        /** @var TotalHelper $totalHelper */
        $totalHelper = self::getContainer()->get('oro_order.order.total.total_helper');
        $totalHelper->fill($this->getReference('simple_order'));
        $this->getEntityManager()->flush();
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'orderproductkititemlineitems']);

        $this->assertResponseContains('cget_product_kit_item_line_item.yml', $response);
    }

    public function testGet(): void
    {
        $response = $this->get(
            [
                'entity' => 'orderproductkititemlineitems',
                'id' => '<toString(@order_product_kit_2_line_item.1_kit_item_line_item.1->id)>',
            ]
        );

        $this->assertResponseContains('get_product_kit_item_line_item.yml', $response);
    }

    public function testGetWithRemovedRelations(): void
    {
        $kitItem = $this->getReference('product-kit-2-kit-item-0');
        $product = $this->getReference('product-1');
        $unit = $this->getReference('product_unit.milliliter');

        $productId = $product->getId();
        $kitItemId = $kitItem->getId();

        $entityManager = $this->getEntityManager();
        $entityManager->remove($kitItem);
        $entityManager->remove($product);
        $entityManager->remove($unit);
        $entityManager->flush();

        $response = $this->get(
            [
                'entity' => 'orderproductkititemlineitems',
                'id' => '<toString(@order_product_kit_2_line_item.1_kit_item_line_item.1->id)>',
            ]
        );

        $responseData = $this->getResponseData('get_product_kit_item_line_item_with_removed_relations.yml');
        $responseData['data']['attributes']['kitItemId'] = $kitItemId;
        $responseData['data']['attributes']['productId'] = $productId;

        $this->assertResponseContains($responseData, $response);
    }

    public function testCreate(): void
    {
        /** @var Order $order */
        $order = $this->getReference('simple_order');
        self::assertEquals('101.54', $order->getSubtotal());
        self::assertEquals('101.54', $order->getTotal());

        $response = $this->post(
            ['entity' => 'orderproductkititemlineitems'],
            'create_product_kit_item_line_item.yml'
        );

        $this->assertResponseContains('create_product_kit_item_line_item.yml', $response);

        $kitItemLineItemId = (int)$this->getResourceId($response);

        /** @var OrderProductKitItemLineItem $kitItemLineItem */
        $kitItemLineItem = $this->getEntityManager()->find(OrderProductKitItemLineItem::class, $kitItemLineItemId);
        $order = $kitItemLineItem->getLineItem()->getOrder();

        // Price should not be changed because we changed configuration of already saved Line Item
        self::assertEquals('101.5400', $order->getSubtotal());
        self::assertEquals('101.5400', $order->getTotal());
    }

    public function testCreateWithProductSku(): void
    {
        $response = $this->post(
            ['entity' => 'orderproductkititemlineitems'],
            'create_product_kit_item_line_item_with_product_sku.yml'
        );

        $this->assertResponseContains('create_product_kit_item_line_item.yml', $response);
    }

    public function testTryToCreateEmptyCurrency(): void
    {
        $data = $this->getRequestData('create_product_kit_item_line_item.yml');
        $data['data']['attributes']['currency'] = '';
        $response = $this->post(
            ['entity' => 'orderproductkititemlineitems'],
            $data,
            [],
            false
        );

        $this->assertResponseContainsValidationError(
            [
                'title' => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/attributes/currency'],
            ],
            $response
        );
    }

    public function testTryToCreateWrongValue(): void
    {
        $data = $this->getRequestData('create_product_kit_item_line_item.yml');
        $data['data']['attributes']['value'] = 'test';
        $response = $this->post(
            ['entity' => 'orderproductkititemlineitems'],
            $data,
            [],
            false
        );

        $this->assertResponseContainsValidationError(
            [
                'title' => 'type constraint',
                'detail' => 'This value should be of type numeric.',
                'source' => ['pointer' => '/data/attributes/value'],
            ],
            $response
        );
    }

    public function testUpdate(): void
    {
        /** @var Order $order */
        $order = $this->getReference('simple_order');
        self::assertEquals('101.54', $order->getSubtotal());
        self::assertEquals('101.54', $order->getTotal());

        $kitItemLineItemId = $this->getReference('order_product_kit_2_line_item.1_kit_item_line_item.1')->getId();

        $response = $this->patch(
            ['entity' => 'orderproductkititemlineitems', 'id' => (string)$kitItemLineItemId],
            [
                'data' => [
                    'type' => 'orderproductkititemlineitems',
                    'id' => (string)$kitItemLineItemId,
                    'attributes' => [
                        'quantity' => 50,
                        'value' => 100,
                        'currency' => 'EUR',
                    ],
                ],
            ]
        );

        $this->assertResponseContains('update_product_kit_item_line_item.yml', $response);

        $kitItemLineItemId = (int)$this->getResourceId($response);

        /** @var OrderProductKitItemLineItem $kitItemLineItem */
        $kitItemLineItem = $this->getEntityManager()->find(OrderProductKitItemLineItem::class, $kitItemLineItemId);
        self::assertEquals(Price::create(100, 'EUR'), $kitItemLineItem->getPrice());

        $order = $kitItemLineItem->getLineItem()->getOrder();
        // Price should not be changed because we changed configuration of already saved Line Item
        self::assertEquals('101.5400', $order->getSubtotal());
        self::assertEquals('101.5400', $order->getTotal());
    }

    public function testGetSubresourceForLineItem(): void
    {
        /** @var OrderProductKitItemLineItem $kitItemLineItem */
        $kitItemLineItem = $this->getReference('order_product_kit_2_line_item.1_kit_item_line_item.1');

        $response = $this->getSubresource(
            [
                'entity' => 'orderproductkititemlineitems',
                'id' => (string)$kitItemLineItem->getId(),
                'association' => 'lineItem',
            ]
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'orderlineitems', 'id' => (string)$kitItemLineItem->getLineItem()->getId()]],
            $response
        );
    }

    public function testGetRelationshipForLineItem(): void
    {
        /** @var OrderProductKitItemLineItem $kitItemLineItem */
        $kitItemLineItem = $this->getReference('order_product_kit_2_line_item.1_kit_item_line_item.1');

        $response = $this->getRelationship(
            [
                'entity' => 'orderproductkititemlineitems',
                'id' => (string)$kitItemLineItem->getId(),
                'association' => 'lineItem',
            ]
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'orderlineitems', 'id' => (string)$kitItemLineItem->getLineItem()->getId()]],
            $response
        );
    }

    public function testTryToUpdateRelationshipForLineItem(): void
    {
        $response = $this->patchRelationship(
            [
                'entity' => 'orderproductkititemlineitems',
                'id' => '<toString(@order_product_kit_2_line_item.1_kit_item_line_item.1->id)>',
                'association' => 'lineItem',
            ],
            ['data' => ['type' => 'orderlineitems', 'id' => '<toString(@product_kit_3_line_item.1->id)>']],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testGetSubresourceForKitItem(): void
    {
        /** @var OrderProductKitItemLineItem $kitItemLineItem */
        $kitItemLineItem = $this->getReference('order_product_kit_2_line_item.1_kit_item_line_item.1');

        $response = $this->getSubresource(
            [
                'entity' => 'orderproductkititemlineitems',
                'id' => (string)$kitItemLineItem->getId(),
                'association' => 'kitItem',
            ]
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'productkititems', 'id' => (string)$kitItemLineItem->getKitItem()->getId()]],
            $response
        );
    }

    public function testGetRelationshipForKitItem(): void
    {
        /** @var OrderProductKitItemLineItem $kitItemLineItem */
        $kitItemLineItem = $this->getReference('order_product_kit_2_line_item.1_kit_item_line_item.1');

        $response = $this->getRelationship(
            [
                'entity' => 'orderproductkititemlineitems',
                'id' => (string)$kitItemLineItem->getId(),
                'association' => 'kitItem',
            ]
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'productkititems', 'id' => (string)$kitItemLineItem->getKitItem()->getId()]],
            $response
        );
    }

    public function testTryToUpdateRelationshipForKitItem(): void
    {
        $response = $this->patchRelationship(
            [
                'entity' => 'orderproductkititemlineitems',
                'id' => '<toString(@order_product_kit_2_line_item.1_kit_item_line_item.1->id)>',
                'association' => 'kitItem',
            ],
            ['data' => ['type' => 'productkititems', 'id' => '<toString(@product_kit_3_line_item.1->id)>']],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testGetSubresourceForProduct(): void
    {
        /** @var OrderProductKitItemLineItem $kitItemLineItem */
        $kitItemLineItem = $this->getReference('order_product_kit_2_line_item.1_kit_item_line_item.1');

        $response = $this->getSubresource(
            [
                'entity' => 'orderproductkititemlineitems',
                'id' => (string)$kitItemLineItem->getId(),
                'association' => 'product',
            ]
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'products', 'id' => (string)$kitItemLineItem->getProduct()->getId()]],
            $response
        );
    }

    public function testGetRelationshipForProduct(): void
    {
        /** @var OrderProductKitItemLineItem $kitItemLineItem */
        $kitItemLineItem = $this->getReference('order_product_kit_2_line_item.1_kit_item_line_item.1');

        $response = $this->getRelationship(
            [
                'entity' => 'orderproductkititemlineitems',
                'id' => (string)$kitItemLineItem->getId(),
                'association' => 'product',
            ]
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'products', 'id' => (string)$kitItemLineItem->getProduct()->getId()]],
            $response
        );
    }

    public function testUpdateRelationshipForProduct(): void
    {
        $kitItemLineItemId = $this->getReference('order_product_kit_2_line_item.1_kit_item_line_item.1')->getId();
        $productId = $this->getReference('product-2')->getId();

        $this->patchRelationship(
            [
                'entity' => 'orderproductkititemlineitems',
                'id' => (string)$kitItemLineItemId,
                'association' => 'product',
            ],
            ['data' => ['type' => 'products', 'id' => (string)$productId]]
        );

        /** @var OrderProductKitItemLineItem $kitItemLineItem */
        $kitItemLineItem = $this->getEntityManager()->find(
            OrderProductKitItemLineItem::class,
            $kitItemLineItemId
        );
        self::assertEquals($productId, $kitItemLineItem->getProduct()->getId());
    }

    public function testGetSubresourceForUnit(): void
    {
        /** @var OrderProductKitItemLineItem $kitItemLineItem */
        $kitItemLineItem = $this->getReference('order_product_kit_2_line_item.1_kit_item_line_item.1');

        $response = $this->getSubresource(
            [
                'entity' => 'orderproductkititemlineitems',
                'id' => (string)$kitItemLineItem->getId(),
                'association' => 'productUnit',
            ]
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'productunits', 'id' => $kitItemLineItem->getProductUnit()->getCode()]],
            $response
        );
    }

    public function testGetRelationshipForUnit(): void
    {
        /** @var OrderProductKitItemLineItem $kitItemLineItem */
        $kitItemLineItem = $this->getReference('order_product_kit_2_line_item.1_kit_item_line_item.1');

        $response = $this->getRelationship(
            [
                'entity' => 'orderproductkititemlineitems',
                'id' => (string)$kitItemLineItem->getId(),
                'association' => 'productUnit',
            ]
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'productunits', 'id' => $kitItemLineItem->getProductUnit()->getCode()]],
            $response
        );
    }

    public function testUpdateRelationshipForUnit(): void
    {
        $kitItemLineItemId = $this->getReference('order_product_kit_2_line_item.1_kit_item_line_item.1')->getId();
        $productUnitCode = $this->getReference('product_unit.liter')->getCode();

        $this->patchRelationship(
            [
                'entity' => 'orderproductkititemlineitems',
                'id' => (string)$kitItemLineItemId,
                'association' => 'productUnit',
            ],
            ['data' => ['type' => 'productunits', 'id' => (string)$productUnitCode]]
        );

        /** @var OrderProductKitItemLineItem $kitItemLineItem */
        $kitItemLineItem = $this->getEntityManager()->find(
            OrderProductKitItemLineItem::class,
            $kitItemLineItemId
        );
        self::assertEquals($productUnitCode, $kitItemLineItem->getProductUnit()->getCode());
    }

    public function testTryToSetNullLineItemForExistingKitItemLineItem(): void
    {
        $kitItemLineItemId = $this->getReference('order_product_kit_2_line_item.1_kit_item_line_item.1')->getId();

        $response = $this->patch(
            ['entity' => 'orderproductkititemlineitems', 'id' => (string)$kitItemLineItemId],
            [
                'data' => [
                    'type' => 'orderproductkititemlineitems',
                    'id' => (string)$kitItemLineItemId,
                    'relationships' => [
                        'lineItem' => [
                            'data' => null,
                        ],
                    ],
                ],
            ],
            [],
            false
        );
        $this->assertResponseValidationErrors(
            [
                [
                    'title' => 'not null constraint',
                    'detail' => 'This value should not be null.',
                    'source' => ['pointer' => '/data/relationships/lineItem/data'],
                ],
                [
                    'title' => 'unchangeable field constraint',
                    'detail' => 'Line Item of the Product Kit Item Line Item cannot be changed once set.',
                    'source' => ['pointer' => '/data/relationships/lineItem/data'],
                ],
            ],
            $response
        );
    }

    public function testTryToChangeLineItemForExistingKitItemLineItem(): void
    {
        $kitItemLineItemId = $this->getReference('order_product_kit_2_line_item.1_kit_item_line_item.1')->getId();
        $lineItemId = $this->getReference('product_kit_3_line_item.1')->getId();

        $response = $this->patch(
            ['entity' => 'orderproductkititemlineitems', 'id' => (string)$kitItemLineItemId],
            [
                'data' => [
                    'type' => 'orderproductkititemlineitems',
                    'id' => (string)$kitItemLineItemId,
                    'relationships' => [
                        'lineItem' => ['data' => ['type' => 'orderlineitems', 'id' => (string)$lineItemId]],
                    ],
                ],
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'unchangeable field constraint',
                'detail' => 'Line Item of the Product Kit Item Line Item cannot be changed once set.',
                'source' => ['pointer' => '/data/relationships/lineItem/data'],
            ],
            $response
        );
    }

    public function testDelete(): void
    {
        /** @var OrderProductKitItemLineItem $kitItemLineItem */
        $kitItemLineItem = $this->getReference('order_product_kit_2_line_item.2_kit_item_line_item.2');
        $kitItemLineItemId = $kitItemLineItem->getId();

        $lineItem = $this->getReference('product_kit_2_line_item.2');
        $lineItemId = $lineItem->getId();
        self::assertEquals(2, $lineItem->getKitItemLineItems()->count());

        /** @var Order $order */
        $order = $this->getReference('simple_order');
        self::assertEquals('101.54', $order->getSubtotal());
        self::assertEquals('101.54', $order->getTotal());

        $this->delete(
            ['entity' => 'orderproductkititemlineitems', 'id' => (string)$kitItemLineItemId]
        );

        self::assertNull($this->getEntityManager()->find(OrderProductKitItemLineItem::class, $kitItemLineItemId));

        $lineItem = $this->getEntityManager()->find(OrderLineItem::class, $lineItemId);
        self::assertEquals(1, $lineItem->getKitItemLineItems()->count());

        $order = $lineItem->getOrder();
        // Price should not be changed because we changed configuration of already saved Line Item
        self::assertEquals('101.5400', $order->getSubtotal());
        self::assertEquals('101.5400', $order->getTotal());
    }

    public function testDeleteList(): void
    {
        /** @var OrderProductKitItemLineItem $kitItemLineItem */
        $kitItemLineItem = $this->getReference('order_product_kit_2_line_item.2_kit_item_line_item.2');
        $kitItemLineItemId = $kitItemLineItem->getId();

        $lineItem = $this->getReference('product_kit_2_line_item.2');
        $lineItemId = $lineItem->getId();
        self::assertEquals(2, $lineItem->getKitItemLineItems()->count());

        $this->cdelete(
            ['entity' => 'orderproductkititemlineitems'],
            ['filter' => ['id' => (string)$kitItemLineItemId]]
        );

        self::assertNull($this->getEntityManager()->find(OrderProductKitItemLineItem::class, $kitItemLineItemId));

        $lineItem = $this->getEntityManager()->find(OrderLineItem::class, $lineItemId);
        self::assertEquals(1, $lineItem->getKitItemLineItems()->count());

        $order = $lineItem->getOrder();
        // Price should not be changed because we changed configuration of already saved Line Item
        self::assertEquals('101.5400', $order->getSubtotal());
        self::assertEquals('101.5400', $order->getTotal());
    }
}
