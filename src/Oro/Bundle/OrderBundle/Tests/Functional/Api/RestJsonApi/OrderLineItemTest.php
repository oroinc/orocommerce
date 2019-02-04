<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class OrderLineItemTest extends RestJsonApiTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroOrderBundle/Tests/Functional/DataFixtures/order_line_items.yml'
        ]);
    }

    public function testGetList()
    {
        $response = $this->cget(['entity' => 'orderlineitems']);

        $this->assertResponseContains('cget_line_item.yml', $response);
    }

    public function testGet()
    {
        $response = $this->get(
            ['entity' => 'orderlineitems', 'id' => '<toString(@order_line_item.1->id)>']
        );

        $this->assertResponseContains('get_line_item.yml', $response);
    }

    public function testCreateWithFreeFormProduct()
    {
        $productUnitId = $this->getReference(LoadProductUnits::BOTTLE)->getCode();
        $productId = $this->getReference(LoadProductData::PRODUCT_1)->getId();
        $productSku = $this->getReference(LoadProductData::PRODUCT_1)->getSku();
        $parentProductId = $this->getReference(LoadProductData::PRODUCT_3)->getId();
        $orderId = $this->getReference(LoadOrders::ORDER_1)->getId();

        $response = $this->post(
            ['entity' => 'orderlineitems'],
            'create_line_item_with_free_form_product.yml'
        );

        $lineItemId = (int)$this->getResourceId($response);

        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getEntityManager()->find(OrderLineItem::class, $lineItemId);
        self::assertEquals($productSku, $lineItem->getProductSku());
        self::assertSame(6.0, $lineItem->getQuantity());
        self::assertEquals($productUnitId, $lineItem->getProductUnit()->getCode());
        self::assertSame('200.0000', $lineItem->getValue());
        self::assertEquals('USD', $lineItem->getCurrency());
        self::assertEquals($productUnitId, $lineItem->getProductUnitCode());
        self::assertEquals($orderId, $lineItem->getOrder()->getId());
        self::assertEquals($productId, $lineItem->getProduct()->getId());
        self::assertEquals($parentProductId, $lineItem->getParentProduct()->getId());
        self::assertEquals(Price::create(200, 'USD'), $lineItem->getPrice());
    }

    public function testCreateWithProductSku()
    {
        $productSku = $this->getReference(LoadProductData::PRODUCT_4)->getSku();

        $response = $this->post(
            ['entity' => 'orderlineitems'],
            'create_line_item_with_product_sku.yml'
        );

        $lineItemId = (int)$this->getResourceId($response);

        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getEntityManager()->find(OrderLineItem::class, $lineItemId);
        self::assertEquals($productSku, $lineItem->getProduct()->getSku());
    }

    public function testUpdate()
    {
        $lineItemId = $this->getReference('order_line_item.1')->getId();

        $this->patch(
            ['entity' => 'orderlineitems', 'id' => (string)$lineItemId],
            [
                'data' => [
                    'type'       => 'orderlineitems',
                    'id'         => (string)$lineItemId,
                    'attributes' => [
                        'quantity' => 50,
                        'value'    => 100,
                        'currency' => 'EUR'
                    ]
                ]
            ]
        );

        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getEntityManager()->find(OrderLineItem::class, $lineItemId);
        self::assertSame(50.0, $lineItem->getQuantity());
        self::assertSame('100.0000', $lineItem->getValue());
        self::assertEquals('EUR', $lineItem->getCurrency());
        self::assertEquals(Price::create(100, 'EUR'), $lineItem->getPrice());
    }

    public function testGetSubresourceForOrder()
    {
        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference('order_line_item.1');
        $lineItemId = $lineItem->getId();
        $orderId = $lineItem->getOrder()->getId();

        $response = $this->getSubresource(
            ['entity' => 'orderlineitems', 'id' => (string)$lineItemId, 'association' => 'order']
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'orders', 'id' => (string)$orderId]],
            $response
        );
    }

    public function testGetRelationshipForOrder()
    {
        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference('order_line_item.1');
        $lineItemId = $lineItem->getId();
        $orderId = $lineItem->getOrder()->getId();

        $response = $this->getRelationship(
            ['entity' => 'orderlineitems', 'id' => (string)$lineItemId, 'association' => 'order']
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'orders', 'id' => (string)$orderId]],
            $response
        );
    }

    public function testUpdateRelationshipForOrder()
    {
        $lineItemId = $this->getReference('order_line_item.1')->getId();
        $targetOrderId = $this->getReference(LoadOrders::MY_ORDER)->getId();

        $this->patchRelationship(
            ['entity' => 'orderlineitems', 'id' => (string)$lineItemId, 'association' => 'order'],
            [
                'data' => [
                    'type' => 'orders',
                    'id'   => (string)$targetOrderId
                ]
            ]
        );

        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getEntityManager()->find(OrderLineItem::class, $lineItemId);
        self::assertEquals($targetOrderId, $lineItem->getOrder()->getId());
    }

    public function testGetSubresourceForProduct()
    {
        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference('order_line_item.1');
        $lineItemId = $lineItem->getId();
        $productId = $lineItem->getProduct()->getId();

        $response = $this->getSubresource(
            ['entity' => 'orderlineitems', 'id' => (string)$lineItemId, 'association' => 'product']
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'products', 'id' => (string)$productId]],
            $response
        );
    }

    public function testGetRelationshipForProduct()
    {
        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference('order_line_item.1');
        $lineItemId = $lineItem->getId();
        $productId = $lineItem->getProduct()->getId();

        $response = $this->getRelationship(
            ['entity' => 'orderlineitems', 'id' => (string)$lineItemId, 'association' => 'product']
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'products', 'id' => (string)$productId]],
            $response
        );
    }

    public function testGetSubresourceForParentProduct()
    {
        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference('order_line_item.1');
        $lineItemId = $lineItem->getId();
        $parentProductId = $lineItem->getParentProduct()->getId();

        $response = $this->getSubresource(
            ['entity' => 'orderlineitems', 'id' => (string)$lineItemId, 'association' => 'parentProduct']
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'products', 'id' => (string)$parentProductId]],
            $response
        );
    }

    public function testGetRelationshipForParentProduct()
    {
        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference('order_line_item.1');
        $lineItemId = $lineItem->getId();
        $parentProductId = $lineItem->getParentProduct()->getId();

        $response = $this->getRelationship(
            ['entity' => 'orderlineitems', 'id' => (string)$lineItemId, 'association' => 'parentProduct']
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'products', 'id' => (string)$parentProductId]],
            $response
        );
    }

    public function testGetSubresourceForProductUnit()
    {
        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference('order_line_item.1');
        $lineItemId = $lineItem->getId();
        $productUnitId = $lineItem->getProductUnit()->getCode();

        $response = $this->getSubresource(
            ['entity' => 'orderlineitems', 'id' => (string)$lineItemId, 'association' => 'productUnit']
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'productunits', 'id' => $productUnitId]],
            $response
        );
    }

    public function testGetRelationshipForProductUnit()
    {
        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference('order_line_item.1');
        $lineItemId = $lineItem->getId();
        $productUnitId = $lineItem->getProductUnit()->getCode();

        $response = $this->getRelationship(
            ['entity' => 'orderlineitems', 'id' => (string)$lineItemId, 'association' => 'productUnit']
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'productunits', 'id' => $productUnitId]],
            $response
        );
    }

    public function testDeleteList()
    {
        $lineItemId = $this->getReference('order_line_item.1')->getId();

        $this->cdelete(
            ['entity' => 'orderlineitems'],
            ['filter' => ['id' => $lineItemId]]
        );

        $lineItem = $this->getEntityManager()->find(OrderLineItem::class, $lineItemId);
        self::assertTrue(null === $lineItem);
    }
}
