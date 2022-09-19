<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Async;

use Doctrine\DBAL\Driver\DriverException;
use Doctrine\DBAL\Exception\DeadlockException;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\MessageQueueBundle\Test\Functional\JobsAwareTestTrait;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductDefaultAttributeFamily;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductInventoryStatuses;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\WebsiteSearchBundle\Async\Topic\WebsiteSearchReindexGranulizedTopic;
use Oro\Bundle\WebsiteSearchBundle\Async\Topic\WebsiteSearchReindexTopic;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\WebsiteSearchExtensionTrait;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;

/**
 * @dbIsolationPerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class WebsiteSearchReindexProcessorTest extends WebTestCase
{
    use JobsAwareTestTrait;
    use MessageQueueExtension;
    use WebsiteSearchExtensionTrait;

    protected function setUp(): void
    {
        $this->initClient();

        self::purgeMessageQueue();

        $this->loadFixtures([
            LoadOrganization::class,
            LoadProductUnits::class,
            LoadProductInventoryStatuses::class,
            LoadProductDefaultAttributeFamily::class,
            '@OroWebsiteSearchBundle/Tests/Functional/DataFixtures/WebsiteSearchReindexProcessorFixture.yml',
        ]);

        self::resetIndex(Product::class);
        self::ensureItemsLoaded(Product::class, 0);
    }

    public function testProcessWhenMessageIsInvalid(): void
    {
        $message = self::sendMessage(WebsiteSearchReindexTopic::getName(), ['invalid_key' => 'invalid_value']);

        self::consume();

        self::assertProcessedMessageStatus(MessageProcessorInterface::REJECT, $message);
        self::assertProcessedMessageProcessor('oro_website_search.async.reindex_processor', $message);

        self::ensureItemsLoaded(Product::class, 0);
    }

    public function testProcessWhenDelayedJobIsCancelled(): void
    {
        $delayedJob = $this->createDelayedJob();
        $this->getJobProcessor()->interruptRootJob($delayedJob->getRootJob());
        self::getContainer()->get('doctrine')->getManagerForClass(Job::class)->refresh($delayedJob);

        self::assertEquals(Job::STATUS_CANCELLED, $delayedJob->getStatus());

        $message = self::sendMessage(
            WebsiteSearchReindexTopic::getName(),
            ['jobId' => $delayedJob->getId(), 'class' => Product::class]
        );

        self::consume();

        self::assertProcessedMessageStatus(MessageProcessorInterface::REJECT, $message);
        self::assertProcessedMessageProcessor('oro_website_search.async.reindex_processor', $message);

        self::assertEquals(Job::STATUS_CANCELLED, $delayedJob->getStatus());

        self::ensureItemsLoaded(Product::class, 0);
    }

    public function testProcessWhenDelayedJobIsInterruptedByException(): void
    {
        $delayedJob = $this->createDelayedJob();

        self::assertEquals(Job::STATUS_NEW, $delayedJob->getStatus());

        $message = self::sendMessage(
            WebsiteSearchReindexTopic::getName(),
            ['jobId' => $delayedJob->getId(), 'class' => Product::class]
        );

        self::getContainer()->get('event_dispatcher')
            ->addListener('oro_website_search.before_reindex', [$this, 'throwBeforeReindexException'], PHP_INT_MAX);

        self::consume();

        self::getContainer()->get('event_dispatcher')
            ->removeListener('oro_website_search.before_reindex', [$this, 'throwBeforeReindexException']);

        self::assertProcessedMessageStatus(MessageProcessorInterface::REJECT, $message);
        self::assertProcessedMessageProcessor('oro_website_search.async.reindex_processor', $message);

        self::assertEquals(Job::STATUS_FAILED, $delayedJob->getStatus());

        self::ensureItemsLoaded(Product::class, 0);
    }

    public function testProcessWhenDelayedJobIsInterruptedByDeadlockException(): void
    {
        $delayedJob = $this->createDelayedJob();

        self::assertEquals(Job::STATUS_NEW, $delayedJob->getStatus());

        $message = self::sendMessage(
            WebsiteSearchReindexTopic::getName(),
            ['jobId' => $delayedJob->getId(), 'class' => Product::class]
        );

        self::getContainer()->get('event_dispatcher')
            ->addListener('oro_website_search.before_reindex', [$this, 'throwDeadlockException'], PHP_INT_MAX);

        self::consume();

        self::getContainer()->get('event_dispatcher')
            ->removeListener('oro_website_search.before_reindex', [$this, 'throwDeadlockException']);

        self::assertProcessedMessageStatus(MessageProcessorInterface::REQUEUE, $message);
        self::assertProcessedMessageProcessor('oro_website_search.async.reindex_processor', $message);

        self::assertEquals(Job::STATUS_FAILED_REDELIVERED, $delayedJob->getStatus());

        self::ensureItemsLoaded(Product::class, 0);
    }

    public function testProcessWhenDelayedJob(): void
    {
        $delayedJob = $this->createDelayedJob();

        self::assertEquals(Job::STATUS_NEW, $delayedJob->getStatus());

        $message = self::sendMessage(
            WebsiteSearchReindexTopic::getName(),
            ['jobId' => $delayedJob->getId(), 'class' => Product::class]
        );
        self::consume();

        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $message);
        self::assertProcessedMessageProcessor('oro_website_search.async.reindex_processor', $message);

        self::assertEquals(Job::STATUS_SUCCESS, $delayedJob->getStatus());
        self::ensureItemsLoaded(Product::class, 110);
    }

    public function testProcessWhenInterruptedByException(): void
    {
        $message = self::sendMessage(WebsiteSearchReindexTopic::getName(), ['class' => Product::class]);

        self::getContainer()->get('event_dispatcher')
            ->addListener('oro_website_search.before_reindex', [$this, 'throwBeforeReindexException'], PHP_INT_MAX);

        self::consume();

        self::getContainer()->get('event_dispatcher')
            ->removeListener('oro_website_search.before_reindex', [$this, 'throwBeforeReindexException']);

        self::assertProcessedMessageStatus(MessageProcessorInterface::REJECT, $message);
        self::assertProcessedMessageProcessor('oro_website_search.async.reindex_processor', $message);

        self::ensureItemsLoaded(Product::class, 0);
    }

    public function testProcessWhenInterruptedByDeadlockException(): void
    {
        $message = self::sendMessage(WebsiteSearchReindexTopic::getName(), ['class' => Product::class]);

        self::getContainer()->get('event_dispatcher')
            ->addListener('oro_website_search.before_reindex', [$this, 'throwDeadlockException'], PHP_INT_MAX);

        self::consume();

        self::getContainer()->get('event_dispatcher')
            ->removeListener('oro_website_search.before_reindex', [$this, 'throwDeadlockException']);

        self::assertProcessedMessageStatus(MessageProcessorInterface::REQUEUE, $message);
        self::assertProcessedMessageProcessor('oro_website_search.async.reindex_processor', $message);

        self::ensureItemsLoaded(Product::class, 0);
    }

    public function testProcess(): void
    {
        $message = self::sendMessage(WebsiteSearchReindexTopic::getName(), ['class' => Product::class]);

        self::consume();

        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $message);
        self::assertProcessedMessageProcessor('oro_website_search.async.reindex_processor', $message);

        self::ensureItemsLoaded(Product::class, 110);
    }

    public function testProcessWhenGranulize(): void
    {
        $message = self::sendMessage(
            WebsiteSearchReindexTopic::getName(),
            ['class' => Product::class, 'granulize' => true]
        );

        // Consumer should process 3 messages: 1 - WebsiteSearchReindexTopic and 2 - WebsiteSearchReindexGranulizedTopic
        self::consume(3);

        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $message);
        self::assertProcessedMessageProcessor('oro_website_search.async.reindex_processor', $message);

        $processedMessages = self::getProcessedMessagesByTopic(WebsiteSearchReindexGranulizedTopic::getName());
        self::assertCount(2, $processedMessages);

        foreach ($processedMessages as $processedMessage) {
            self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $processedMessage['message']);
            self::assertProcessedMessageProcessor(
                'oro_website_search.async.reindex_processor.granulized',
                $processedMessage['message']
            );
        }

        self::ensureItemsLoaded(Product::class, 110);
    }

    public function throwBeforeReindexException(): void
    {
        throw new \RuntimeException('Exception');
    }

    public function throwDeadlockException(): void
    {
        throw new DeadlockException('Deadlock detected', $this->createMock(DriverException::class));
    }
}
