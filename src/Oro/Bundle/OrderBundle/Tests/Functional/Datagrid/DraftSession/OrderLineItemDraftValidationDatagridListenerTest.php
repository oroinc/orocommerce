<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Functional\Datagrid\DraftSession;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\Manager as DatagridManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
final class OrderLineItemDraftValidationDatagridListenerTest extends WebTestCase
{
    private DatagridManager $datagridManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadOrders::class,
            LoadProductData::class,
            LoadProductUnits::class,
        ]);

        $this->datagridManager = self::getContainer()->get('oro_datagrid.datagrid.manager');
    }

    public function testHandlesEmptyOrder(): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        $datagrid = $this->getDatagrid($order, UUIDGenerator::v4());
        $data = $datagrid->getData();

        self::assertEmpty($data['data']);
    }

    public function testAddsIsValidPropertyToDatagridResults(): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        // Create a valid line item
        $lineItem = new OrderLineItem();
        $lineItem->setProduct($product);
        $lineItem->setQuantity(10);
        $lineItem->setProductUnit($product->getPrimaryUnitPrecision()->getUnit());
        $lineItem->setPrice(Price::create(100, 'USD'));
        $order->addLineItem($lineItem);

        $entityManager = self::getContainer()->get('doctrine')->getManagerForClass(OrderLineItem::class);
        $entityManager->persist($lineItem);
        $entityManager->flush();

        $datagrid = $this->getDatagrid($order, UUIDGenerator::v4());
        $data = $datagrid->getData();

        self::assertNotEmpty($data['data']);
        $firstRow = $data['data'][0];
        self::assertArrayHasKey('isValid', $firstRow);
        self::assertEquals('1', $firstRow['isValid']);
    }

    public function testMarksInvalidLineItemsWithIsValidFalse(): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        // Create an invalid line item (missing required price)
        $lineItem = new OrderLineItem();
        $lineItem->setProduct($product);
        $lineItem->setQuantity(10);
        $lineItem->setProductUnit($product->getPrimaryUnitPrecision()->getUnit());
        // Price is missing - this should make it invalid
        $order->addLineItem($lineItem);

        $entityManager = self::getContainer()->get('doctrine')->getManagerForClass(OrderLineItem::class);
        $entityManager->persist($lineItem);
        $entityManager->flush();

        $datagrid = $this->getDatagrid($order, UUIDGenerator::v4());
        $data = $datagrid->getData();

        self::assertNotEmpty($data['data']);
        $firstRow = $data['data'][0];
        self::assertArrayHasKey('isValid', $firstRow);
        self::assertEquals('0', $firstRow['isValid']);
    }

    public function testInvalidLineItemsAreMovedToTop(): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        // Create a valid line item first
        $validLineItem = new OrderLineItem();
        $validLineItem->setProduct($product);
        $validLineItem->setQuantity(10);
        $validLineItem->setProductUnit($product->getPrimaryUnitPrecision()->getUnit());
        $validLineItem->setPrice(Price::create(100, 'USD'));
        $order->addLineItem($validLineItem);

        // Create an invalid line item second
        $invalidLineItem = new OrderLineItem();
        $invalidLineItem->setProduct($product);
        $invalidLineItem->setQuantity(5);
        $invalidLineItem->setProductUnit($product->getPrimaryUnitPrecision()->getUnit());
        // Missing price - invalid
        $order->addLineItem($invalidLineItem);

        $entityManager = self::getContainer()->get('doctrine')->getManagerForClass(OrderLineItem::class);
        $entityManager->persist($validLineItem);
        $entityManager->persist($invalidLineItem);
        $entityManager->flush();

        $datagrid = $this->getDatagrid($order, UUIDGenerator::v4());
        $data = $datagrid->getData();

        self::assertCount(2, $data['data']);

        // Invalid line item should be first
        self::assertEquals('0', $data['data'][0]['isValid']);
        self::assertEquals($invalidLineItem->getId(), $data['data'][0]['orderLineItemId']);

        // Valid line item should be second
        self::assertEquals('1', $data['data'][1]['isValid']);
        self::assertEquals($validLineItem->getId(), $data['data'][1]['orderLineItemId']);
    }

    public function testIsValidOrderingPreservesExistingOrderByCriteria(): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        // Create valid line items with different quantities
        $validLineItem1 = new OrderLineItem();
        $validLineItem1->setProduct($product);
        $validLineItem1->setQuantity(30);
        $validLineItem1->setProductUnit($product->getPrimaryUnitPrecision()->getUnit());
        $validLineItem1->setPrice(Price::create(100, 'USD'));
        $order->addLineItem($validLineItem1);

        $validLineItem2 = new OrderLineItem();
        $validLineItem2->setProduct($product);
        $validLineItem2->setQuantity(10);
        $validLineItem2->setProductUnit($product->getPrimaryUnitPrecision()->getUnit());
        $validLineItem2->setPrice(Price::create(100, 'USD'));
        $order->addLineItem($validLineItem2);

        // Create invalid line items with different quantities
        $invalidLineItem1 = new OrderLineItem();
        $invalidLineItem1->setProduct($product);
        $invalidLineItem1->setQuantity(50);
        $invalidLineItem1->setProductUnit($product->getPrimaryUnitPrecision()->getUnit());
        // Missing price - invalid
        $order->addLineItem($invalidLineItem1);

        $invalidLineItem2 = new OrderLineItem();
        $invalidLineItem2->setProduct($product);
        $invalidLineItem2->setQuantity(20);
        $invalidLineItem2->setProductUnit($product->getPrimaryUnitPrecision()->getUnit());
        // Missing price - invalid
        $order->addLineItem($invalidLineItem2);

        $entityManager = self::getContainer()->get('doctrine')->getManagerForClass(OrderLineItem::class);
        $entityManager->persist($validLineItem1);
        $entityManager->persist($validLineItem2);
        $entityManager->persist($invalidLineItem1);
        $entityManager->persist($invalidLineItem2);
        $entityManager->flush();

        // Apply explicit sorter: quantity ASC
        $datagrid = $this->getDatagridWithSorter($order, UUIDGenerator::v4(), 'quantity', 'ASC');
        $data = $datagrid->getData();

        self::assertCount(4, $data['data']);

        // First two should be invalid items (isValid = 0), sorted by quantity ASC
        self::assertEquals('0', $data['data'][0]['isValid']);
        self::assertEquals($invalidLineItem2->getId(), (int)$data['data'][0]['orderLineItemId']); // quantity 20

        self::assertEquals('0', $data['data'][1]['isValid']);
        self::assertEquals($invalidLineItem1->getId(), (int)$data['data'][1]['orderLineItemId']); // quantity 50

        // Last two should be valid items (isValid = 1), sorted by quantity ASC
        self::assertEquals('1', $data['data'][2]['isValid']);
        self::assertEquals($validLineItem2->getId(), (int)$data['data'][2]['orderLineItemId']); // quantity 10

        self::assertEquals('1', $data['data'][3]['isValid']);
        self::assertEquals($validLineItem1->getId(), (int)$data['data'][3]['orderLineItemId']); // quantity 30
    }

    public function testOrderingWhenAllLineItemsAreValid(): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        // Create valid line items with different quantities
        $validLineItem1 = new OrderLineItem();
        $validLineItem1->setProduct($product);
        $validLineItem1->setQuantity(15);
        $validLineItem1->setProductUnit($product->getPrimaryUnitPrecision()->getUnit());
        $validLineItem1->setPrice(Price::create(100, 'USD'));
        $order->addLineItem($validLineItem1);

        $validLineItem2 = new OrderLineItem();
        $validLineItem2->setProduct($product);
        $validLineItem2->setQuantity(25);
        $validLineItem2->setProductUnit($product->getPrimaryUnitPrecision()->getUnit());
        $validLineItem2->setPrice(Price::create(150, 'USD'));
        $order->addLineItem($validLineItem2);

        $validLineItem3 = new OrderLineItem();
        $validLineItem3->setProduct($product);
        $validLineItem3->setQuantity(5);
        $validLineItem3->setProductUnit($product->getPrimaryUnitPrecision()->getUnit());
        $validLineItem3->setPrice(Price::create(75, 'USD'));
        $order->addLineItem($validLineItem3);

        $entityManager = self::getContainer()->get('doctrine')->getManagerForClass(OrderLineItem::class);
        $entityManager->persist($validLineItem1);
        $entityManager->persist($validLineItem2);
        $entityManager->persist($validLineItem3);
        $entityManager->flush();

        // Apply explicit sorter: quantity ASC
        $datagrid = $this->getDatagridWithSorter($order, UUIDGenerator::v4(), 'quantity', 'ASC');
        $data = $datagrid->getData();

        self::assertCount(3, $data['data']);

        // All items should be valid (isValid = 1) and sorted by quantity ASC
        self::assertEquals('1', $data['data'][0]['isValid']);
        self::assertEquals($validLineItem3->getId(), (int)$data['data'][0]['orderLineItemId']); // quantity 5

        self::assertEquals('1', $data['data'][1]['isValid']);
        self::assertEquals($validLineItem1->getId(), (int)$data['data'][1]['orderLineItemId']); // quantity 15

        self::assertEquals('1', $data['data'][2]['isValid']);
        self::assertEquals($validLineItem2->getId(), (int)$data['data'][2]['orderLineItemId']); // quantity 25
    }

    private function getDatagrid(Order $order, string $draftSessionUuid): DatagridInterface
    {
        return $this->datagridManager->getDatagrid(
            'order-line-items-edit-grid',
            [
                'order_id' => $order->getId(),
                'order_draft_id' => null,
                'draft_session_uuid' => $draftSessionUuid,
            ]
        );
    }

    private function getDatagridWithSorter(
        Order $order,
        string $draftSessionUuid,
        string $sortBy,
        string $direction
    ): DatagridInterface {
        return $this->datagridManager->getDatagrid(
            'order-line-items-edit-grid',
            [
                'order_id' => $order->getId(),
                'order_draft_id' => null,
                'draft_session_uuid' => $draftSessionUuid,
                '_sort_by' => [$sortBy => $direction],
            ]
        );
    }
}
