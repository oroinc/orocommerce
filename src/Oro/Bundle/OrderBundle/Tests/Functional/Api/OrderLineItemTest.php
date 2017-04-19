<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Api;

use Doctrine\Common\Persistence\ObjectManager;
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
use Symfony\Component\HttpFoundation\Response;

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
        $response = $this->cget(['entity' => $this->getEntityType(OrderLineItem::class)]);
        $this->assertResponseContains(__DIR__.'/responses/line_item/get_items.yml', $response);
    }

    public function testGet()
    {
        $response = $this->get([
            'entity' => $this->getEntityType(OrderLineItem::class),
            'id' => '<toString(@order_line_item.1->id)>',
        ]);

        $this->assertResponseContains(__DIR__.'/responses/line_item/get_item.yml', $response);
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

        $requestData = [
            'data' => [
                'type' => $this->getEntityType(OrderLineItem::class),
                'id' => (string)$item->getId(),
                'attributes' => [
                    'quantity' => $newQuantity,
                    'value' => $newValue,
                    'currency' => $newCurrency,
                ],
            ],
        ];

        $uri = $this->getUrl(
            'oro_rest_api_patch',
            [
                'entity' => $this->getEntityType(OrderLineItem::class),
                'id' => $item->getId(),
            ]
        );
        $response = $this->request('PATCH', $uri, $requestData);

        /** @var OrderLineItem $updatedItem */
        $updatedItem = $this->getManager()
            ->getRepository(OrderLineItem::class)
            ->find($item->getId());

        static::assertSame(Response::HTTP_OK, $response->getStatusCode());
        static::assertEquals($newQuantity, $updatedItem->getQuantity());

        $updatedItem->setQuantity($oldQuantity)
            ->setValue($oldValue)
            ->setCurrency($oldCurrency);

        $this->getManager()->flush();
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
            ['entity' => $this->getEntityType(OrderLineItem::class)],
            __DIR__.'/responses/line_item/create_with_free_form_product.yml'
        );

        /** @var OrderLineItem $item */
        $item = $this->getManager()
            ->getRepository(OrderLineItem::class)
            ->findOneBy(['freeFormProduct' => 'Test']);

        static::assertSame($this->getReference(LoadProductData::PRODUCT_1)->getSku(), $item->getProductSku());
        static::assertEquals(6, $item->getQuantity());
        static::assertEquals(
            $this->getReference(LoadProductUnits::BOTTLE)->getCode(),
            $item->getProductUnit()->getCode()
        );
        static::assertEquals(200, $item->getValue());
        static::assertSame('USD', $item->getCurrency());
        static::assertSame($this->getReference(LoadProductUnits::BOTTLE)->getCode(), $item->getProductUnitCode());

        static::assertSame($this->getReference(LoadOrders::ORDER_1)->getId(), $item->getOrder()->getId());
        static::assertSame($this->getReference(LoadProductData::PRODUCT_1)->getId(), $item->getProduct()->getId());
        static::assertSame(
            $this->getReference(LoadProductData::PRODUCT_3)->getId(),
            $item->getParentProduct()->getId()
        );
        static::assertSame(
            $this->getReference(LoadProductUnits::BOTTLE)->getCode(),
            $item->getProductUnit()->getCode()
        );

        static::assertEquals(Price::create(200, 'USD'), $item->getPrice());

        $this->removeItem($item);
    }

    public function testCreateWithProductSku()
    {
        $this->post(
            ['entity' => $this->getEntityType(OrderLineItem::class)],
            __DIR__.'/responses/line_item/create_with_product_sku.yml'
        );

        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_4);

        /** @var OrderLineItem $item */
        $item = $this->getManager()
            ->getRepository(OrderLineItem::class)
            ->findOneBy(['productSku' => $product->getSku()]);

        static::assertEquals($product->getSku(), $item->getProduct()->getSku());

        $this->removeItem($item);
    }

    public function testPatchOrderRelationship()
    {
        $item = $this->getFirstLineItem();

        /** @var Order $order2 */
        $order2 = $this->getReference(LoadOrders::MY_ORDER);

        $uri = $this->getUrl(
            'oro_rest_api_patch_relationship',
            [
                'entity' => $this->getEntityType(OrderLineItem::class),
                'id' => (string)$item->getId(),
                'association' => 'order',
            ]
        );
        $data = [
            'data' => [
                'type' => $this->getEntityType(Order::class),
                'id' => (string)$order2->getId(),
            ],
        ];
        $response = $this->request('PATCH', $uri, $data);

        /** @var OrderLineItem $updatedItem */
        $updatedItem = $this->getManager()
            ->getRepository(OrderLineItem::class)
            ->find($item->getId());

        static::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        static::assertSame($order2->getId(), $updatedItem->getOrder()->getId());
    }

    public function testDeleteByFilter()
    {
        $item = $this->getFirstLineItem();
        $itemId = $item->getId();

        $uri = $this->getUrl(
            'oro_rest_api_cget',
            ['entity' => $this->getEntityType(OrderLineItem::class)]
        );
        $response = $this->request(
            'DELETE',
            $uri,
            ['filter' => ['id' => $itemId]]
        );

        $this->getManager()->clear();

        $removedDiscount = $this->getManager()
            ->getRepository(OrderLineItem::class)
            ->find($itemId);

        static::assertResponseStatusCodeEquals($response, Response::HTTP_NO_CONTENT);
        static::assertNull($removedDiscount);
    }

    /**
     * @param int    $entityId
     * @param string $associationName
     * @param string $expectedAssociationId
     */
    private function assertGetSubResource($entityId, $associationName, $expectedAssociationId)
    {
        $uri = $this->getUrl(
            'oro_rest_api_get_subresource',
            [
                'entity' => $this->getEntityType(OrderLineItem::class),
                'id' => $entityId,
                'association' => $associationName,
            ]
        );
        $response = $this->request('GET', $uri);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $resource = json_decode($response->getContent(), true)['data'];

        static::assertEquals($expectedAssociationId, $resource['id']);
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
        $uri = $this->getUrl(
            'oro_rest_api_get_relationship',
            [
                'entity' => $this->getEntityType(OrderLineItem::class),
                'id' => $entityId,
                'association' => $associationName,
            ]
        );
        $response = $this->request('GET', $uri);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);

        $expected = [
            'data' => [
                    'type' => $this->getEntityType($associationClassName),
                    'id' => (string)$expectedAssociationId,
                ],
        ];

        static::assertEquals($expected, $content);
    }

    /**
     * @return OrderLineItem
     */
    private function getFirstLineItem()
    {
        return $this->getReference(LoadOrderLineItems::ITEM_1);
    }

    /**
     * @return ObjectManager
     */
    private function getManager()
    {
        return static::getContainer()->get('doctrine')->getManager();
    }

    /**
     * @param OrderLineItem $item
     */
    private function removeItem(OrderLineItem $item)
    {
        $this->getManager()->remove($item);
        $this->getManager()->flush();
        $this->getManager()->clear();
    }
}
