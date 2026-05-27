<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Entity\Repository\OrderLineItemRepository;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderLineItemData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

final class OrderLineItemRepositoryTest extends WebTestCase
{
    private OrderLineItemRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures(
            [
                LoadOrderLineItemData::class,
            ]
        );

        $this->repository = self::getContainer()
            ->get('doctrine')
            ->getRepository(OrderLineItem::class);
    }

    public function testFindOrderLineItemWithRelations(): void
    {
        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference(LoadOrderLineItemData::ORDER_LINEITEM_2);

        $lineItemWithRelations = $this->repository->findOrderLineItemWithRelations($lineItem->getId());

        self::assertNotNull($lineItemWithRelations);
        self::assertSame($lineItem->getId(), $lineItemWithRelations->getId());
        self::assertSame($lineItem->getProductSku(), $lineItemWithRelations->getProductSku());
        self::assertEquals($lineItem->getQuantity(), $lineItemWithRelations->getQuantity());
        self::assertEquals($lineItem->getPrice(), $lineItemWithRelations->getPrice());
    }

    public function testFindOrderLineItemWithRelationsLoadsProduct(): void
    {
        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference(LoadOrderLineItemData::ORDER_LINEITEM_2);

        $lineItemWithRelations = $this->repository->findOrderLineItemWithRelations($lineItem->getId());

        self::assertNotNull($lineItemWithRelations);

        // Verify product is accessible
        $product = $lineItemWithRelations->getProduct();
        self::assertNotNull($product);
        self::assertTrue(
            !method_exists($product, '__isInitialized') || $product->__isInitialized(),
            'Product proxy must be initialized'
        );
        self::assertSame($lineItem->getProduct()->getId(), $product->getId());
    }

    public function testFindOrderLineItemWithRelationsLoadsProductUnit(): void
    {
        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference(LoadOrderLineItemData::ORDER_LINEITEM_2);

        $lineItemWithRelations = $this->repository->findOrderLineItemWithRelations($lineItem->getId());

        self::assertNotNull($lineItemWithRelations);

        // Verify product unit is accessible
        $productUnit = $lineItemWithRelations->getProductUnit();
        self::assertNotNull($productUnit);
        self::assertTrue(
            !method_exists($productUnit, '__isInitialized') || $productUnit->__isInitialized(),
            'ProductUnit proxy must be initialized'
        );
        self::assertSame($lineItem->getProductUnit()->getCode(), $productUnit->getCode());
    }

    public function testFindOrderLineItemWithRelationsLoadsKitItemLineItems(): void
    {
        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference(LoadOrderLineItemData::ORDER_LINEITEM_2);

        $lineItemWithRelations = $this->repository->findOrderLineItemWithRelations($lineItem->getId());

        self::assertNotNull($lineItemWithRelations);

        // Verify kit item line items collection is accessible (even if empty)
        $kitItemLineItems = $lineItemWithRelations->getKitItemLineItems();
        self::assertInstanceOf(PersistentCollection::class, $kitItemLineItems);
        self::assertTrue($kitItemLineItems->isInitialized(), 'KitItemLineItems collection must be initialized');
        // For a simple product, this collection should be empty or have kit items if it's a kit product
        self::assertIsIterable($kitItemLineItems);
    }

    public function testFindOrderLineItemWithRelationsReturnsNullForNonExistentId(): void
    {
        $result = $this->repository->findOrderLineItemWithRelations(0);

        self::assertNull($result);
    }
}
