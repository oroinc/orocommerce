<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderLineItems;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;

class OrderLineItemTest extends RestJsonApiTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures([
            LoadOrderLineItems::class,
        ]);
    }

    public function testGetList()
    {
        $response = $this->cget(['entity' => 'orderlineitems']);

        $this->assertResponseContains('line_item_get_list.yml', $response);
    }

    public function testGet()
    {
        $response = $this->get([
            'entity' => 'orderlineitems',
            'id' => '<toString(@order_line_item.1->id)>',
        ]);

        $this->assertResponseContains('line_item_get.yml', $response);
    }

    public function testUpdate()
    {
        $item = $this->getFirstLineItem();

        $oldQuantity = $item->getQuantity();
        $oldValue = $item->getValue();
        $oldCurrency = $item->getCurrency();

        $newQuantity = 50;
        $newValue = 100;
        $newCurrency = 'EUR';

        $this->patch(
            ['entity' => 'orderlineitems', 'id' => $item->getId()],
            [
                'data' => [
                    'type' => 'orderlineitems',
                    'id' => (string)$item->getId(),
                    'attributes' => [
                        'quantity' => $newQuantity,
                        'value' => $newValue,
                        'currency' => $newCurrency,
                    ],
                ],
            ]
        );

        /** @var OrderLineItem $updatedItem */
        $updatedItem = $this->getEntityManager()
            ->getRepository(OrderLineItem::class)
            ->find($item->getId());

        self::assertEquals($newQuantity, $updatedItem->getQuantity());

        $updatedItem->setQuantity($oldQuantity)
            ->setValue($oldValue)
            ->setCurrency($oldCurrency);

        $this->getEntityManager()->flush();
    }

    public function testGetSubResources()
    {
        $item = $this->getFirstLineItem();

        $this->assertGetSubResource($item->getId(), 'order', $item->getOrder()->getId());
        $this->assertGetSubResource($item->getId(), 'product', $item->getProduct()->getId());
        $this->assertGetSubResource($item->getId(), 'parentProduct', $item->getParentProduct()->getId());
        $this->assertGetSubResource($item->getId(), 'productUnit', $item->getProductUnit()->getCode());
    }

    public function testGetRelationships()
    {
        $item = $this->getFirstLineItem();

        $this->assertGetRelationship($item->getId(), 'order', Order::class, $item->getOrder()->getId());
        $this->assertGetRelationship($item->getId(), 'product', Product::class, $item->getProduct()->getId());
        $this->assertGetRelationship(
            $item->getId(),
            'parentProduct',
            Product::class,
            $item->getParentProduct()->getId()
        );
        $this->assertGetRelationship(
            $item->getId(),
            'productUnit',
            ProductUnit::class,
            $item->getProductUnit()->getCode()
        );
    }

    public function testCreateWithFreeFormProduct()
    {
        $this->post(
            ['entity' => 'orderlineitems'],
            'line_item_create_with_free_form_product.yml'
        );

        /** @var OrderLineItem $item */
        $item = $this->getEntityManager()
            ->getRepository(OrderLineItem::class)
            ->findOneBy(['freeFormProduct' => 'Test']);

        self::assertSame($this->getReference(LoadProductData::PRODUCT_1)->getSku(), $item->getProductSku());
        self::assertEquals(6, $item->getQuantity());
        self::assertEquals(
            $this->getReference(LoadProductUnits::BOTTLE)->getCode(),
            $item->getProductUnit()->getCode()
        );
        self::assertEquals(200, $item->getValue());
        self::assertSame('USD', $item->getCurrency());
        self::assertSame($this->getReference(LoadProductUnits::BOTTLE)->getCode(), $item->getProductUnitCode());

        self::assertSame($this->getReference(LoadOrders::ORDER_1)->getId(), $item->getOrder()->getId());
        self::assertSame($this->getReference(LoadProductData::PRODUCT_1)->getId(), $item->getProduct()->getId());
        self::assertSame(
            $this->getReference(LoadProductData::PRODUCT_3)->getId(),
            $item->getParentProduct()->getId()
        );
        self::assertSame(
            $this->getReference(LoadProductUnits::BOTTLE)->getCode(),
            $item->getProductUnit()->getCode()
        );

        self::assertEquals(Price::create(200, 'USD'), $item->getPrice());

        $this->removeItem($item);
    }

    public function testCreateWithProductSku()
    {
        $this->post(
            ['entity' => 'orderlineitems'],
            'line_item_create_with_product_sku.yml'
        );

        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_4);

        /** @var OrderLineItem $item */
        $item = $this->getEntityManager()
            ->getRepository(OrderLineItem::class)
            ->findOneBy(['productSku' => $product->getSku()]);

        self::assertEquals($product->getSku(), $item->getProduct()->getSku());

        $this->removeItem($item);
    }

    public function testPatchOrderRelationship()
    {
        $item = $this->getFirstLineItem();

        /** @var Order $order2 */
        $order2 = $this->getReference(LoadOrders::MY_ORDER);

        $this->patchRelationship(
            ['entity' => 'orderlineitems', 'id' => (string)$item->getId(), 'association' => 'order'],
            [
                'data' => [
                    'type' => $this->getEntityType(Order::class),
                    'id' => (string)$order2->getId(),
                ],
            ]
        );

        /** @var OrderLineItem $updatedItem */
        $updatedItem = $this->getEntityManager()
            ->getRepository(OrderLineItem::class)
            ->find($item->getId());

        self::assertSame($order2->getId(), $updatedItem->getOrder()->getId());
    }

    public function testDeleteByFilter()
    {
        $item = $this->getFirstLineItem();
        $itemId = $item->getId();

        $this->cdelete(
            ['entity' => 'orderlineitems'],
            ['filter' => ['id' => $itemId]]
        );

        $removedDiscount = $this->getEntityManager()
            ->getRepository(OrderLineItem::class)
            ->find($itemId);

        self::assertNull($removedDiscount);
    }

    /**
     * @param int    $entityId
     * @param string $associationName
     * @param string $expectedAssociationId
     */
    private function assertGetSubResource($entityId, $associationName, $expectedAssociationId)
    {
        $response = $this->getSubresource(
            ['entity' => 'orderlineitems', 'id' => $entityId, 'association' => $associationName]
        );

        $result = json_decode($response->getContent(), true);

        self::assertEquals($expectedAssociationId, $result['data']['id']);
    }

    /**
     * @param int    $entityId
     * @param string $associationName
     * @param string $associationClassName
     * @param string $expectedAssociationId
     */
    private function assertGetRelationship(
        $entityId,
        $associationName,
        $associationClassName,
        $expectedAssociationId
    ) {
        $response = $this->getRelationship(
            ['entity' => 'orderlineitems', 'id' => $entityId, 'association' => $associationName]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $this->getEntityType($associationClassName),
                    'id' => (string)$expectedAssociationId
                ]
            ],
            $response
        );
    }

    /**
     * @return OrderLineItem
     */
    private function getFirstLineItem()
    {
        return $this->getReference(LoadOrderLineItems::ITEM_1);
    }

    /**
     * @param OrderLineItem $item
     */
    private function removeItem(OrderLineItem $item)
    {
        $this->getEntityManager()->remove($item);
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();
    }
}
