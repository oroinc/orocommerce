<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Entity\Repository\OrderLineItemRepository;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderLineItemData;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderLineItemDraftData;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\DraftSession\Manager\DraftSessionOrmFilterManager;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class OrderLineItemRepositoryTest extends WebTestCase
{
    private OrderLineItemRepository $repository;

    private DraftSessionOrmFilterManager $draftSessionOrmFilterManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures(
            [
                LoadOrderLineItemData::class,
                LoadOrderLineItemDraftData::class,
            ]
        );

        $this->repository = self::getContainer()
            ->get('doctrine')
            ->getRepository(OrderLineItem::class);

        $this->draftSessionOrmFilterManager = self::getContainer()
            ->get('oro_order.draft_session.manager.draft_session_orm_filter_manager');
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->draftSessionOrmFilterManager->enable();

        parent::tearDown();
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

    public function testFindOrderLineItemDraftWithRelationsReturnsNullWhenDraftSessionUuidIsEmpty(): void
    {
        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference(LoadOrderLineItemData::ORDER_LINEITEM_2);

        $result = $this->repository->findOrderLineItemDraftWithRelations($lineItem->getId(), '');

        self::assertNull($result);
    }

    public function testFindOrderLineItemDraftWithRelationsReturnsNullWhenNoMatchingDraft(): void
    {
        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference(LoadOrderLineItemData::ORDER_LINEITEM_2);

        $this->draftSessionOrmFilterManager->disable();

        $result = $this->repository->findOrderLineItemDraftWithRelations(
            $lineItem->getId(),
            UUIDGenerator::v4()
        );

        self::assertNull($result);
    }

    public function testFindOrderLineItemDraftWithRelationsReturnsNullWhenNonExistentLineItemId(): void
    {
        $this->draftSessionOrmFilterManager->disable();

        /** @var OrderLineItem $draftLineItem */
        $draftLineItem = $this->getReference(LoadOrderLineItemDraftData::ORDER_LINE_ITEM_DRAFT_1);

        $result = $this->repository->findOrderLineItemDraftWithRelations(
            0,
            $draftLineItem->getDraftSessionUuid()
        );

        self::assertNull($result);
    }

    public function testFindOrderLineItemDraftWithRelationsFindsExistingDraft(): void
    {
        $this->draftSessionOrmFilterManager->disable();

        /** @var OrderLineItem $sourceLineItem */
        $sourceLineItem = $this->getReference(LoadOrderLineItemDraftData::ORDER_LINE_ITEM_1);
        /** @var OrderLineItem $draftLineItem */
        $draftLineItem = $this->getReference(LoadOrderLineItemDraftData::ORDER_LINE_ITEM_DRAFT_1);

        $result = $this->repository->findOrderLineItemDraftWithRelations(
            $sourceLineItem->getId(),
            $draftLineItem->getDraftSessionUuid()
        );

        self::assertNotNull($result);
        self::assertSame($draftLineItem->getId(), $result->getId());
        self::assertSame($sourceLineItem->getId(), $result->getDraftSource()->getId());
        self::assertEquals($draftLineItem->getDraftSessionUuid(), $result->getDraftSessionUuid());
    }

    public function testFindOrderLineItemDraftWithRelationsLoadsProduct(): void
    {
        $this->draftSessionOrmFilterManager->disable();

        /** @var OrderLineItem $sourceLineItem */
        $sourceLineItem = $this->getReference(LoadOrderLineItemDraftData::ORDER_LINE_ITEM_1);
        /** @var OrderLineItem $draftLineItem */
        $draftLineItem = $this->getReference(LoadOrderLineItemDraftData::ORDER_LINE_ITEM_DRAFT_1);

        $result = $this->repository->findOrderLineItemDraftWithRelations(
            $sourceLineItem->getId(),
            $draftLineItem->getDraftSessionUuid()
        );

        self::assertNotNull($result);

        // Verify product is loaded
        $product = $result->getProduct();
        self::assertNotNull($product);
        self::assertTrue(
            !method_exists($product, '__isInitialized') || $product->__isInitialized(),
            'Product proxy must be initialized'
        );
        self::assertSame($draftLineItem->getProduct()->getId(), $product->getId());
    }

    public function testFindOrderLineItemDraftWithRelationsLoadsProductUnit(): void
    {
        $this->draftSessionOrmFilterManager->disable();

        /** @var OrderLineItem $sourceLineItem */
        $sourceLineItem = $this->getReference(LoadOrderLineItemDraftData::ORDER_LINE_ITEM_1);
        /** @var OrderLineItem $draftLineItem */
        $draftLineItem = $this->getReference(LoadOrderLineItemDraftData::ORDER_LINE_ITEM_DRAFT_1);

        $result = $this->repository->findOrderLineItemDraftWithRelations(
            $sourceLineItem->getId(),
            $draftLineItem->getDraftSessionUuid()
        );

        self::assertNotNull($result);

        // Verify product unit is loaded
        $productUnit = $result->getProductUnit();
        self::assertNotNull($productUnit);
        self::assertTrue(
            !method_exists($productUnit, '__isInitialized') || $productUnit->__isInitialized(),
            'ProductUnit proxy must be initialized'
        );
        self::assertSame($draftLineItem->getProductUnit()->getCode(), $productUnit->getCode());
    }

    public function testFindOrderLineItemDraftWithRelationsLoadsKitItemLineItems(): void
    {
        $this->draftSessionOrmFilterManager->disable();

        /** @var OrderLineItem $sourceLineItem */
        $sourceLineItem = $this->getReference(LoadOrderLineItemDraftData::ORDER_LINE_ITEM_1);
        /** @var OrderLineItem $draftLineItem */
        $draftLineItem = $this->getReference(LoadOrderLineItemDraftData::ORDER_LINE_ITEM_DRAFT_1);

        $result = $this->repository->findOrderLineItemDraftWithRelations(
            $sourceLineItem->getId(),
            $draftLineItem->getDraftSessionUuid()
        );

        self::assertNotNull($result);

        // Verify kit item line items collection is loaded (even if empty)
        $kitItemLineItems = $result->getKitItemLineItems();
        self::assertInstanceOf(PersistentCollection::class, $kitItemLineItems);
        self::assertTrue($kitItemLineItems->isInitialized(), 'KitItemLineItems collection must be initialized');
        self::assertIsIterable($kitItemLineItems);
    }

    public function testFindOrderLineItemDraftWithRelationsForNewlyCreatedLineItem(): void
    {
        $this->draftSessionOrmFilterManager->disable();

        /** @var OrderLineItem $draftLineItem */
        $draftLineItem = $this->getReference(LoadOrderLineItemDraftData::ORDER_LINE_ITEM_3);

        // When a new line item is created (no source), we pass the draft's own ID
        // so it should find itself when draftSource is null
        $result = $this->repository->findOrderLineItemDraftWithRelations(
            $draftLineItem->getId(),
            $draftLineItem->getDraftSessionUuid()
        );

        self::assertNotNull($result);
        self::assertSame($draftLineItem->getId(), $result->getId());
    }
}
