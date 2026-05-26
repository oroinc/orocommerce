<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Functional\DraftSession\Provider;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderLineItemDraftData;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\DraftSession\Manager\DraftSessionOrmFilterManager;
use Oro\Component\DraftSession\Provider\GenericEntityDraftRepository;

/**
 * @dbIsolationPerTest
 */
final class GenericEntityDraftRepositoryTest extends WebTestCase
{
    private GenericEntityDraftRepository $repository;
    private DraftSessionOrmFilterManager $filterManager;
    private FeatureChecker $featureChecker;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadOrderLineItemDraftData::class]);

        $this->repository = self::getContainer()
            ->get('oro_order.draft_session.provider.order_line_item_entity_draft_repository');
        $this->filterManager = self::getContainer()
            ->get('oro_order.draft_session.manager.draft_session_orm_filter_manager');

        $this->filterManager->disable();

        $this->featureChecker = self::getContainer()->get('oro_featuretoggle.checker.feature_checker');
        $this->featureChecker->setFeatureEnabled('order_draft_edit_mode', true);
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->filterManager->enable();
        $this->featureChecker->setFeatureEnabled('order_draft_edit_mode', false);

        parent::tearDown();
    }

    public function testSupportsReturnsTrueForOrderLineItemClass(): void
    {
        self::assertTrue($this->repository->supports(OrderLineItem::class));
    }

    public function testSupportsReturnsFalseForOrderClass(): void
    {
        self::assertFalse($this->repository->supports(Order::class));
    }

    public function testHasEntityDraftReturnsTrueWhenCalledWithRegularEntityAndDraftExistsForIt(): void
    {
        /** @var OrderLineItem $regularLineItem */
        $regularLineItem = $this->getReference(LoadOrderLineItemDraftData::ORDER_LINE_ITEM_1);

        /** @var OrderLineItem $draftLineItem */
        $draftLineItem = $this->getReference(LoadOrderLineItemDraftData::ORDER_LINE_ITEM_DRAFT_1);
        $draftSessionUuid = $draftLineItem->getDraftSessionUuid();

        // ORDER_LINE_ITEM_DRAFT_1 has draftSource = ORDER_LINE_ITEM_1, so the repository
        // should find the draft when the regular entity and its session UUID are provided.
        self::assertTrue($this->repository->hasEntityDraft($regularLineItem, $draftSessionUuid));
    }

    public function testHasEntityDraftReturnsTrueWhenCalledWithDraftItself(): void
    {
        /** @var OrderLineItem $draftLineItem */
        $draftLineItem = $this->getReference(LoadOrderLineItemDraftData::ORDER_LINE_ITEM_DRAFT_1);
        $draftSessionUuid = $draftLineItem->getDraftSessionUuid();

        // Repository should also find a draft when the draft entity itself is passed,
        // as it matches on entity.id = entityId condition.
        self::assertTrue($this->repository->hasEntityDraft($draftLineItem, $draftSessionUuid));
    }

    public function testHasEntityDraftReturnsFalseWhenEntityHasNoId(): void
    {
        // A new entity with no ID causes getEntityOrDraftId() to return null,
        // so the repository skips the query and returns false immediately.
        $newLineItem = new OrderLineItem();

        self::assertFalse($this->repository->hasEntityDraft($newLineItem, UUIDGenerator::v4()));
    }

    public function testHasEntityDraftReturnsFalseWhenNoDraftWithGivenUuid(): void
    {
        /** @var OrderLineItem $regularLineItem */
        $regularLineItem = $this->getReference(LoadOrderLineItemDraftData::ORDER_LINE_ITEM_1);

        self::assertFalse($this->repository->hasEntityDraft($regularLineItem, UUIDGenerator::v4()));
    }

    public function testHasEntityDraftReturnsFalseWhenDraftDoesNotBelongToEntity(): void
    {
        /** @var OrderLineItem $regularLineItem */
        $regularLineItem = $this->getReference(LoadOrderLineItemDraftData::ORDER_LINE_ITEM_2);

        // ORDER_LINE_ITEM_DRAFT_1 belongs to ORDER_LINE_ITEM_1, not ORDER_LINE_ITEM_2.
        /** @var OrderLineItem $draftLineItem */
        $draftLineItem = $this->getReference(LoadOrderLineItemDraftData::ORDER_LINE_ITEM_DRAFT_1);
        $draftSessionUuid = $draftLineItem->getDraftSessionUuid();

        self::assertFalse($this->repository->hasEntityDraft($regularLineItem, $draftSessionUuid));
    }

    public function testFindEntityDraftReturnsDraftWhenCalledWithRegularEntity(): void
    {
        /** @var OrderLineItem $regularLineItem */
        $regularLineItem = $this->getReference(LoadOrderLineItemDraftData::ORDER_LINE_ITEM_1);

        /** @var OrderLineItem $draftLineItem */
        $draftLineItem = $this->getReference(LoadOrderLineItemDraftData::ORDER_LINE_ITEM_DRAFT_1);
        $draftSessionUuid = $draftLineItem->getDraftSessionUuid();
        $draftLineItemId = $draftLineItem->getId();

        $result = $this->repository->findEntityDraft($regularLineItem, $draftSessionUuid);

        self::assertInstanceOf(OrderLineItem::class, $result);
        self::assertSame($draftLineItemId, $result->getId());
        self::assertSame($draftSessionUuid, $result->getDraftSessionUuid());
    }

    public function testFindEntityDraftReturnsDraftWhenCalledWithDraftItself(): void
    {
        /** @var OrderLineItem $draftLineItem */
        $draftLineItem = $this->getReference(LoadOrderLineItemDraftData::ORDER_LINE_ITEM_DRAFT_1);
        $draftSessionUuid = $draftLineItem->getDraftSessionUuid();
        $draftLineItemId = $draftLineItem->getId();

        $result = $this->repository->findEntityDraft($draftLineItem, $draftSessionUuid);

        self::assertInstanceOf(OrderLineItem::class, $result);
        self::assertSame($draftLineItemId, $result->getId());
    }

    public function testFindEntityDraftReturnsNullWhenEntityHasNoId(): void
    {
        // A new entity with no ID causes getEntityOrDraftId() to return null,
        // so the repository skips the query and returns null immediately.
        $newLineItem = new OrderLineItem();

        self::assertNull($this->repository->findEntityDraft($newLineItem, UUIDGenerator::v4()));
    }

    public function testFindEntityDraftReturnsNullWhenNoDraftForEntity(): void
    {
        /** @var OrderLineItem $regularLineItem */
        $regularLineItem = $this->getReference(LoadOrderLineItemDraftData::ORDER_LINE_ITEM_1);

        $result = $this->repository->findEntityDraft($regularLineItem, UUIDGenerator::v4());

        self::assertNull($result);
    }

    public function testFindEntityDraftReturnsNullWhenDraftDoesNotBelongToEntity(): void
    {
        /** @var OrderLineItem $regularLineItem */
        $regularLineItem = $this->getReference(LoadOrderLineItemDraftData::ORDER_LINE_ITEM_2);

        // ORDER_LINE_ITEM_DRAFT_1 belongs to ORDER_LINE_ITEM_1, not ORDER_LINE_ITEM_2.
        /** @var OrderLineItem $draftLineItem */
        $draftLineItem = $this->getReference(LoadOrderLineItemDraftData::ORDER_LINE_ITEM_DRAFT_1);
        $draftSessionUuid = $draftLineItem->getDraftSessionUuid();

        $result = $this->repository->findEntityDraft($regularLineItem, $draftSessionUuid);

        self::assertNull($result);
    }
}
