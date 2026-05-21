<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Functional\DraftSession\Provider;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOutdatedDraftOrderData;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\DraftSession\Entity\EntityDraftAwareInterface;
use Oro\Component\DraftSession\Manager\DraftSessionOrmFilterManager;
use Oro\Component\DraftSession\Provider\ExclusiveEntityDraftRepository;

/**
 * @dbIsolationPerTest
 */
final class ExclusiveEntityDraftRepositoryTest extends WebTestCase
{
    private ExclusiveEntityDraftRepository $repository;
    private DraftSessionOrmFilterManager $filterManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadOutdatedDraftOrderData::class]);

        $this->repository = self::getContainer()
            ->get('oro_order.draft_session.provider.order_entity_draft_repository');
        $this->filterManager = self::getContainer()
            ->get('oro_order.draft_session.manager.draft_session_orm_filter_manager');

        $this->filterManager->disable();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->filterManager->enable();
        parent::tearDown();
    }

    public function testSupportsReturnsTrueForOrderClass(): void
    {
        self::assertTrue($this->repository->supports(Order::class));
    }

    public function testSupportsReturnsTrueForEntityDraftAwareInterface(): void
    {
        // ExclusiveEntityDraftRepository is instantiated with Order::class which implements
        // EntityDraftAwareInterface, so supports() should return true for the interface as well.
        self::assertTrue($this->repository->supports(EntityDraftAwareInterface::class));
    }

    public function testSupportsReturnsFalseForUnrelatedClass(): void
    {
        self::assertFalse($this->repository->supports(OrderLineItem::class));
    }

    public function testHasEntityDraftReturnsTrueWhenDraftWithUuidExists(): void
    {
        /** @var Order $draftOrder */
        $draftOrder = $this->getReference(LoadOutdatedDraftOrderData::OUTDATED_DRAFT_ORDER_1);
        $draftSessionUuid = $draftOrder->getDraftSessionUuid();

        /** @var Order $regularOrder */
        $regularOrder = $this->getReference(LoadOutdatedDraftOrderData::REGULAR_ORDER);

        // The entityOrDraft argument is not used in the query by ExclusiveEntityDraftRepository.
        self::assertTrue($this->repository->hasEntityDraft($regularOrder, $draftSessionUuid));
    }

    public function testHasEntityDraftReturnsFalseWhenNoDraftWithUuid(): void
    {
        /** @var Order $regularOrder */
        $regularOrder = $this->getReference(LoadOutdatedDraftOrderData::REGULAR_ORDER);

        self::assertFalse($this->repository->hasEntityDraft($regularOrder, UUIDGenerator::v4()));
    }

    public function testFindEntityDraftReturnsDraftWhenDraftWithUuidExists(): void
    {
        /** @var Order $draftOrder */
        $draftOrder = $this->getReference(LoadOutdatedDraftOrderData::OUTDATED_DRAFT_ORDER_1);
        $draftSessionUuid = $draftOrder->getDraftSessionUuid();
        $draftOrderId = $draftOrder->getId();

        /** @var Order $regularOrder */
        $regularOrder = $this->getReference(LoadOutdatedDraftOrderData::REGULAR_ORDER);

        // The entityOrDraft argument is not used in the query by ExclusiveEntityDraftRepository.
        $result = $this->repository->findEntityDraft($regularOrder, $draftSessionUuid);

        self::assertInstanceOf(Order::class, $result);
        self::assertSame($draftOrderId, $result->getId());
        self::assertSame($draftSessionUuid, $result->getDraftSessionUuid());
    }

    public function testFindEntityDraftReturnsNullWhenNoDraftWithUuid(): void
    {
        /** @var Order $regularOrder */
        $regularOrder = $this->getReference(LoadOutdatedDraftOrderData::REGULAR_ORDER);

        $result = $this->repository->findEntityDraft($regularOrder, UUIDGenerator::v4());

        self::assertNull($result);
    }
}
