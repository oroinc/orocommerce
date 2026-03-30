<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Functional\Async;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\OrderBundle\Async\Topic\OrderDraftsCleanupTopic;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOutdatedDraftOrderData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\DraftSession\Async\EntityDraftsCleanupProcessor;
use Oro\Component\DraftSession\Manager\DraftSessionOrmFilterManager;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;

/**
 * @dbIsolationPerTest
 */
class EntityDraftsCleanupProcessorTest extends WebTestCase
{
    use MessageQueueExtension;

    private EntityDraftsCleanupProcessor $processor;
    private EntityManagerInterface $entityManager;
    private DraftSessionOrmFilterManager $filterManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadOutdatedDraftOrderData::class]);

        $this->processor = self::getContainer()->get('oro_order.async.entity_drafts_cleanup_processor');
        $this->filterManager = self::getContainer()->get(
            'oro_order.draft_session.manager.draft_session_orm_filter_manager'
        );

        $doctrine = self::getContainer()->get('doctrine');
        $this->entityManager = $doctrine->getManagerForClass(Order::class);

        $this->filterManager->disable();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->filterManager->enable();
        parent::tearDown();
    }

    public function testProcessRemovesOutdatedDrafts(): void
    {
        // Get outdated drafts from fixtures (10 days and 25 days old)
        $outdatedOrder1 = $this->getReference(LoadOutdatedDraftOrderData::OUTDATED_DRAFT_ORDER_1);
        $outdatedOrder2 = $this->getReference(LoadOutdatedDraftOrderData::OUTDATED_DRAFT_ORDER_2);
        $recentDraft = $this->getReference(LoadOutdatedDraftOrderData::RECENT_DRAFT_ORDER);
        $regularOrder = $this->getReference(LoadOutdatedDraftOrderData::REGULAR_ORDER);

        $outdatedOrder1Id = $outdatedOrder1->getId();
        $outdatedOrder2Id = $outdatedOrder2->getId();
        $recentDraftId = $recentDraft->getId();
        $regularOrderId = $regularOrder->getId();

        $sentMessage = self::sendMessage(
            OrderDraftsCleanupTopic::getName(),
            ['draftLifetimeDays' => 7]
        );
        self::consumeMessage($sentMessage);
        $this->filterManager->disable();

        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $sentMessage);
        self::assertProcessedMessageProcessor(
            'oro_order.async.entity_drafts_cleanup_processor',
            $sentMessage
        );
        $this->entityManager->clear();

        // Assert outdated drafts were deleted (10 and 25 days old, threshold is 7)
        self::assertNull($this->entityManager->find(Order::class, $outdatedOrder1Id));
        self::assertNull($this->entityManager->find(Order::class, $outdatedOrder2Id));

        // Assert recent draft and regular order still exist
        self::assertNotNull($this->entityManager->find(Order::class, $recentDraftId));
        self::assertNotNull($this->entityManager->find(Order::class, $regularOrderId));
    }

    public function testProcessWithCustomDraftLifetime(): void
    {
        // Get order that is 10 days old
        $outdatedOrder1 = $this->getReference(LoadOutdatedDraftOrderData::OUTDATED_DRAFT_ORDER_1);
        $outdatedOrder1Id = $outdatedOrder1->getId();

        // Send and consume message with 15 days threshold (should NOT delete 10 days old order)
        $sentMessage = self::sendMessage(
            OrderDraftsCleanupTopic::getName(),
            ['draftLifetimeDays' => 15]
        );
        self::consumeMessage($sentMessage);
        $this->filterManager->disable();

        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $sentMessage);
        $this->entityManager->clear();

        // Assert draft was NOT deleted (10 days old, threshold is 15 days)
        self::assertNotNull($this->entityManager->find(Order::class, $outdatedOrder1Id));

        // Now send with 5 days threshold (should delete 10 days old order)
        $sentMessage2 = self::sendMessage(
            OrderDraftsCleanupTopic::getName(),
            ['draftLifetimeDays' => 5]
        );
        self::consumeMessage($sentMessage2);
        $this->filterManager->disable();
        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $sentMessage2);
        $this->entityManager->clear();

        // Assert draft was deleted (10 days old, threshold is 5 days)
        self::assertNull($this->entityManager->find(Order::class, $outdatedOrder1Id));
    }

    public function testProcessWithNoOutdatedDrafts(): void
    {
        // Use a very long threshold (100 days) so nothing gets deleted
        $sentMessage = self::sendMessage(
            OrderDraftsCleanupTopic::getName(),
            ['draftLifetimeDays' => 100]
        );

        self::consumeMessage($sentMessage);
        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $sentMessage);
    }
}
