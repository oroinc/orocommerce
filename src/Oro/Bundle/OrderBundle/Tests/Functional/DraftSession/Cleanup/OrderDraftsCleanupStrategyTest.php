<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Functional\DraftSession\Cleanup;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOutdatedDraftOrderData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\DraftSession\Cleanup\GenericEntityDraftsCleanupStrategy;
use Oro\Component\DraftSession\Manager\DraftSessionOrmFilterManager;

/**
 * @dbIsolationPerTest
 */
final class OrderDraftsCleanupStrategyTest extends WebTestCase
{
    private const string THRESHOLD_1_DAY = 'today -1 day';
    private const string THRESHOLD_7_DAYS = 'today -7 days';
    private const string THRESHOLD_20_DAYS = 'today -20 days';
    private const string THRESHOLD_100_DAYS = 'today -100 days';

    private const int DEFAULT_BATCH_SIZE = 100;
    private const int SMALL_BATCH_SIZE = 1;

    private GenericEntityDraftsCleanupStrategy $strategy;
    private EntityManagerInterface $em;
    private DraftSessionOrmFilterManager $filterManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadOutdatedDraftOrderData::class]);

        $this->strategy = self::getContainer()->get('oro_order.draft_session.cleanup.order_strategy');
        $this->em = self::getContainer()->get('doctrine')->getManagerForClass(Order::class);
        $this->filterManager = self::getContainer()->get(
            'oro_order.draft_session.manager.draft_session_orm_filter_manager'
        );
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->filterManager->enable();
        parent::tearDown();
    }

    public function testRemoveOutdatedDraftsRemovesOnlyOutdatedOrders(): void
    {
        $outdatedOrder1 = $this->getReference(LoadOutdatedDraftOrderData::OUTDATED_DRAFT_ORDER_1);
        $outdatedOrder2 = $this->getReference(LoadOutdatedDraftOrderData::OUTDATED_DRAFT_ORDER_2);
        $recentDraftOrder = $this->getReference(LoadOutdatedDraftOrderData::RECENT_DRAFT_ORDER);
        $regularOrder = $this->getReference(LoadOutdatedDraftOrderData::REGULAR_ORDER);

        $outdatedOrder1Id = $outdatedOrder1->getId();
        $outdatedOrder2Id = $outdatedOrder2->getId();
        $recentDraftOrderId = $recentDraftOrder->getId();
        $regularOrderId = $regularOrder->getId();

        // Threshold: 7 days ago (should remove orders updated more than 7 days ago)
        $threshold = new \DateTime(self::THRESHOLD_7_DAYS, new \DateTimeZone('UTC'));

        $removedCount = $this->strategy->cleanupEntityDrafts($threshold, self::DEFAULT_BATCH_SIZE);

        // Should remove 2 outdated draft orders (10 days and 25 days old)
        self::assertEquals(2, $removedCount);

        // Verify outdated orders are deleted
        $this->assertOrderNotExists($outdatedOrder1Id);
        $this->assertOrderNotExists($outdatedOrder2Id);

        // Verify a recent draft order is NOT deleted (only 3 days old)
        $foundRecentDraft = $this->assertOrderExists($recentDraftOrderId);
        self::assertNotNull($foundRecentDraft->getDraftSessionUuid());

        // Verify the regular order is NOT deleted (has no draftSessionUuid)
        $foundRegularOrder = $this->assertOrderExists($regularOrderId);
        self::assertNull($foundRegularOrder->getDraftSessionUuid());
    }

    public function testRemoveOutdatedDraftsWithStricterThreshold(): void
    {
        $outdatedOrder1 = $this->getReference(LoadOutdatedDraftOrderData::OUTDATED_DRAFT_ORDER_1);
        $outdatedOrder2 = $this->getReference(LoadOutdatedDraftOrderData::OUTDATED_DRAFT_ORDER_2);
        $recentDraftOrder = $this->getReference(LoadOutdatedDraftOrderData::RECENT_DRAFT_ORDER);

        $outdatedOrder1Id = $outdatedOrder1->getId();
        $outdatedOrder2Id = $outdatedOrder2->getId();
        $recentDraftOrderId = $recentDraftOrder->getId();

        // Stricter threshold: 20 days ago (should remove only very old orders)
        $threshold = new \DateTime(self::THRESHOLD_20_DAYS, new \DateTimeZone('UTC'));

        $removedCount = $this->strategy->cleanupEntityDrafts($threshold, self::DEFAULT_BATCH_SIZE);

        // Should remove only 1 order (25 days old)
        self::assertEquals(1, $removedCount);

        // Verify only the oldest order is deleted
        $this->assertOrderExists($outdatedOrder1Id); // 10 days - kept
        $this->assertOrderNotExists($outdatedOrder2Id); // 25 days - removed
        $this->assertOrderExists($recentDraftOrderId); // 3 days - kept
    }

    public function testRemoveOutdatedDraftsWithSmallBatchSize(): void
    {
        $outdatedOrder1 = $this->getReference(LoadOutdatedDraftOrderData::OUTDATED_DRAFT_ORDER_1);
        $outdatedOrder2 = $this->getReference(LoadOutdatedDraftOrderData::OUTDATED_DRAFT_ORDER_2);

        $outdatedOrder1Id = $outdatedOrder1->getId();
        $outdatedOrder2Id = $outdatedOrder2->getId();

        // Small batch size to test batch processing
        $threshold = new \DateTime(self::THRESHOLD_7_DAYS, new \DateTimeZone('UTC'));

        $removedCount = $this->strategy->cleanupEntityDrafts($threshold, self::SMALL_BATCH_SIZE);

        // Should still remove both orders (processed in 2 batches)
        self::assertEquals(2, $removedCount);

        $this->assertOrderNotExists($outdatedOrder1Id);
        $this->assertOrderNotExists($outdatedOrder2Id);
    }

    public function testRemoveOutdatedDraftsWhenNoDraftsToRemove(): void
    {
        // Very old threshold - no orders are this old
        $threshold = new \DateTime(self::THRESHOLD_100_DAYS, new \DateTimeZone('UTC'));

        $removedCount = $this->strategy->cleanupEntityDrafts($threshold, self::DEFAULT_BATCH_SIZE);

        // Should remove nothing
        self::assertEquals(0, $removedCount);
    }

    public function testRemoveOutdatedDraftsOnlyRemovesOrdersWithDraftSessionUuid(): void
    {
        $regularOrder = $this->getReference(LoadOutdatedDraftOrderData::REGULAR_ORDER);
        $regularOrderId = $regularOrder->getId();

        // Even with a recent threshold, regular orders (without draftSessionUuid) should not be removed
        $threshold = new \DateTime(self::THRESHOLD_1_DAY, new \DateTimeZone('UTC'));

        $removedCount = $this->strategy->cleanupEntityDrafts($threshold, self::DEFAULT_BATCH_SIZE);

        // Should remove 3 draft orders, but not the regular order
        self::assertEquals(3, $removedCount);

        // Regular order should still exist
        $foundRegularOrder = $this->assertOrderExists($regularOrderId);
        self::assertNull($foundRegularOrder->getDraftSessionUuid());
    }

    private function assertOrderExists(int $orderId, string $message = ''): Order
    {
        $this->filterManager->disable();
        $this->em->clear();

        $order = $this->em->find(Order::class, $orderId);
        self::assertNotNull(
            $order,
            $message ?: sprintf('Order with ID %d should exist', $orderId)
        );

        return $order;
    }

    private function assertOrderNotExists(int $orderId, string $message = ''): void
    {
        $this->filterManager->disable();
        $this->em->clear();

        $order = $this->em->find(Order::class, $orderId);
        self::assertNull(
            $order,
            $message ?: sprintf('Order with ID %d should be deleted', $orderId)
        );
    }
}
