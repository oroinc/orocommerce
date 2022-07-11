<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Engine\AsyncMessaging;

use Doctrine\DBAL\Driver\DriverException;
use Doctrine\DBAL\Exception\DeadlockException;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\MessageQueueBundle\Test\Functional\JobsAwareTestTrait;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Engine\AsyncIndexer;
use Oro\Bundle\WebsiteSearchBundle\Engine\AsyncMessaging\SearchMessageProcessor;
use Oro\Component\MessageQueue\Client\Config as MessageQueueConfig;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SearchMessageProcessorTest extends WebTestCase
{
    use JobsAwareTestTrait;

    private SearchMessageProcessor $processor;

    protected function setUp(): void
    {
        $this->initClient();

        $this->logger = new ArrayLogger();

        $this->processor = self::getContainer()->get('oro_website_search.search_processor');
    }

    public function testProcessWhenDelayedJobAndTopicIsInvalid(): void
    {
        $delayedJob = $this->createDelayedJob();

        $message = new Message();
        $message->setMessageId(UUIDGenerator::v4());
        $message->setProperties([MessageQueueConfig::PARAMETER_TOPIC_NAME => 'invalid']);
        $message->setBody(['jobId' => $delayedJob->getId()]);

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );
    }

    public function testProcessWhenDelayedJobAndMessageIsInvalid(): void
    {
        $delayedJob = $this->createDelayedJob();

        $message = new Message();
        $message->setMessageId(UUIDGenerator::v4());
        $message->setProperties([MessageQueueConfig::PARAMETER_TOPIC_NAME => AsyncIndexer::TOPIC_SAVE]);
        $message->setBody(['jobId' => $delayedJob->getId(), 'invalid_key' => 'invalid_value']);

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );
    }

    public function testProcessWhenDelayedJobIsCancelled(): void
    {
        $delayedJob = $this->createDelayedJob();
        $this->getJobProcessor()->interruptRootJob($delayedJob->getRootJob());
        self::getContainer()->get('doctrine')->getManagerForClass(Job::class)->refresh($delayedJob);

        $message = new Message();
        $message->setMessageId(UUIDGenerator::v4());
        $message->setProperties([MessageQueueConfig::PARAMETER_TOPIC_NAME => AsyncIndexer::TOPIC_SAVE]);
        $message->setBody(
            [
                'jobId' => $delayedJob->getId(),
                'class' => Product::class,
                'granulize' => false,
            ]
        );

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );
    }

    public function testProcessWhenDelayedJobIsInterruptedByException(): void
    {
        $delayedJob = $this->createDelayedJob();

        $message = new Message();
        $message->setMessageId(UUIDGenerator::v4());
        $message->setProperties([MessageQueueConfig::PARAMETER_TOPIC_NAME => AsyncIndexer::TOPIC_REINDEX]);
        $message->setBody(
            [
                'jobId' => $delayedJob->getId(),
                'class' => Product::class,
                'context' => [],
            ]
        );

        self::getContainer()->get('event_dispatcher')
            ->addListener('oro_website_search.before_reindex', [$this, 'throwBeforeReindexException'], PHP_INT_MAX);

        self::assertEquals(
            MessageProcessorInterface::REQUEUE,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );

        self::getContainer()->get('event_dispatcher')
            ->removeListener('oro_website_search.before_reindex', [$this, 'throwBeforeReindexException']);
    }

    public function testProcessWhenDelayedJobIsInterruptedByDeadlockException(): void
    {
        $delayedJob = $this->createDelayedJob();

        $message = new Message();
        $message->setMessageId(UUIDGenerator::v4());
        $message->setProperties([MessageQueueConfig::PARAMETER_TOPIC_NAME => AsyncIndexer::TOPIC_REINDEX]);
        $message->setBody(
            [
                'jobId' => $delayedJob->getId(),
                'class' => Product::class,
                'context' => [],
            ]
        );

        self::getContainer()->get('event_dispatcher')
            ->addListener('oro_website_search.before_reindex', [$this, 'throwDeadlockException'], PHP_INT_MAX);

        self::assertEquals(
            MessageProcessorInterface::REQUEUE,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );

        self::getContainer()->get('event_dispatcher')
            ->removeListener('oro_website_search.before_reindex', [$this, 'throwDeadlockException']);
    }

    public function testProcessWhenDelayedJob(): void
    {
        $delayedJob = $this->createDelayedJob();

        $message = new Message();
        $message->setMessageId(UUIDGenerator::v4());
        $message->setProperties([MessageQueueConfig::PARAMETER_TOPIC_NAME => AsyncIndexer::TOPIC_REINDEX]);
        $message->setBody(
            [
                'jobId' => $delayedJob->getId(),
                'class' => Product::class,
                'granulize' => false,
            ]
        );

        self::assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );
    }

    public function testProcessWhenTopicIsInvalid(): void
    {
        $message = new Message();
        $message->setMessageId(UUIDGenerator::v4());
        $message->setProperties([MessageQueueConfig::PARAMETER_TOPIC_NAME => 'invalid']);
        $message->setBody([]);

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );
    }

    public function testProcessWhenMessageIsInvalid(): void
    {
        $message = new Message();
        $message->setMessageId(UUIDGenerator::v4());
        $message->setProperties([MessageQueueConfig::PARAMETER_TOPIC_NAME => AsyncIndexer::TOPIC_SAVE]);
        $message->setBody(['invalid_key' => 'invalid_value']);

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );
    }

    public function testProcessWhenInterruptedByException(): void
    {
        $message = new Message();
        $message->setMessageId(UUIDGenerator::v4());
        $message->setProperties([MessageQueueConfig::PARAMETER_TOPIC_NAME => AsyncIndexer::TOPIC_REINDEX]);
        $message->setBody(
            [
                'class' => Product::class,
                'granulize' => false,
            ]
        );

        self::getContainer()->get('event_dispatcher')
            ->addListener('oro_website_search.before_reindex', [$this, 'throwBeforeReindexException'], PHP_INT_MAX);

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );

        self::getContainer()->get('event_dispatcher')
            ->removeListener('oro_website_search.before_reindex', [$this, 'throwBeforeReindexException']);
    }

    public function testProcessWhenInterruptedByDeadlockException(): void
    {
        $message = new Message();
        $message->setMessageId(UUIDGenerator::v4());
        $message->setProperties([MessageQueueConfig::PARAMETER_TOPIC_NAME => AsyncIndexer::TOPIC_REINDEX]);
        $message->setBody(
            [
                'class' => Product::class,
                'granulize' => false,
            ]
        );

        self::getContainer()->get('event_dispatcher')
            ->addListener('oro_website_search.before_reindex', [$this, 'throwDeadlockException'], PHP_INT_MAX);

        self::assertEquals(
            MessageProcessorInterface::REQUEUE,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );

        self::getContainer()->get('event_dispatcher')
            ->removeListener('oro_website_search.before_reindex', [$this, 'throwDeadlockException']);
    }

    public function testProcess(): void
    {
        $message = new Message();
        $message->setMessageId(UUIDGenerator::v4());
        $message->setProperties([MessageQueueConfig::PARAMETER_TOPIC_NAME => AsyncIndexer::TOPIC_REINDEX]);
        $message->setBody(
            [
                'class' => Product::class,
                'granulize' => false,
            ]
        );

        self::assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );
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
