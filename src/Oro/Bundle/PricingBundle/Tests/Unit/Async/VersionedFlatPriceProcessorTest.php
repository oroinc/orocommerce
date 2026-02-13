<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\MessageQueueBundle\Client\BufferedMessageProducer;
use Oro\Bundle\PricingBundle\Async\Topic\ResolveFlatPriceTopic;
use Oro\Bundle\PricingBundle\Async\Topic\ResolveVersionedFlatPriceTopic;
use Oro\Bundle\PricingBundle\Async\VersionedFlatPriceProcessor;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\ProductBundle\Async\Topic\ReindexRequestItemProductsByRelatedJobIdTopic;
use Oro\Bundle\ProductBundle\Storage\ProductWebsiteReindexRequestDataStorage;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class VersionedFlatPriceProcessorTest extends TestCase
{
    private VersionedFlatPriceProcessor $processor;
    private MessageProducerInterface|MockObject $producer;
    private JobRunner|MockObject $jobRunner;
    private ManagerRegistry|MockObject $doctrine;
    private ShardManager|MockObject $shardManager;
    private ProductWebsiteReindexRequestDataStorage|MockObject $dataStorage;
    private LoggerInterface|MockObject $logger;

    protected function setUp(): void
    {
        $this->producer = $this->createMock(MessageProducerInterface::class);
        $this->jobRunner = $this->createMock(JobRunner::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->shardManager = $this->createMock(ShardManager::class);
        $this->dataStorage = $this->createMock(ProductWebsiteReindexRequestDataStorage::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->processor = new VersionedFlatPriceProcessor(
            $this->producer,
            $this->jobRunner,
            $this->doctrine,
            $this->shardManager,
            $this->dataStorage
        );
        $this->processor->setLogger($this->logger);
    }

    public function testGetSubscribedTopics(): void
    {
        self::assertSame(
            [ResolveVersionedFlatPriceTopic::getName()],
            VersionedFlatPriceProcessor::getSubscribedTopics()
        );
    }

    public function testProcess(): void
    {
        $this->assertProcessCalls($this->processor, $this->producer);
    }

    public function testProcessWithBufferedProducer(): void
    {
        $bufferedProducer = $this->createMock(BufferedMessageProducer::class);
        $bufferedProducer->expects(self::once())
            ->method('disableBuffering');
        $bufferedProducer->expects(self::once())
            ->method('enableBuffering');

        $processor = new VersionedFlatPriceProcessor(
            $bufferedProducer,
            $this->jobRunner,
            $this->doctrine,
            $this->shardManager,
            $this->dataStorage
        );
        $processor->setLogger($this->logger);

        $this->assertProcessCalls($processor, $bufferedProducer);
    }

    public function testProcessWithBufferedProducerEnsuresEnableBufferingOnException(): void
    {
        $bufferedProducer = $this->createMock(BufferedMessageProducer::class);
        $bufferedProducer->expects(self::once())
            ->method('disableBuffering');
        $bufferedProducer->expects(self::once())
            ->method('enableBuffering');

        $processor = new VersionedFlatPriceProcessor(
            $bufferedProducer,
            $this->jobRunner,
            $this->doctrine,
            $this->shardManager,
            $this->dataStorage
        );
        $processor->setLogger($this->logger);

        $body = ['priceLists' => [1], 'version' => 1];

        $e = new \Exception('Test exception');
        $this->jobRunner
            ->expects(self::once())
            ->method('runUniqueByMessage')
            ->willThrowException($e);

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during queue message processing',
                ['exception' => $e, 'topic' => ResolveFlatPriceTopic::NAME]
            );

        self::assertSame(
            $processor::REJECT,
            $processor->process($this->getMessage($body), $this->getSession())
        );
    }

    private function assertProcessCalls(
        VersionedFlatPriceProcessor $processor,
        MessageProducerInterface|MockObject $producer
    ): void {
        $body = ['priceLists' => [1], 'version' => 1];

        $productPriceRepository = $this->createMock(ProductPriceRepository::class);
        $productPriceRepository
            ->expects(self::once())
            ->method('getProductsByPriceListAndVersion')
            ->willReturn([[1], [2], [3]]);

        $this->doctrine
            ->expects(self::any())
            ->method('getRepository')
            ->willReturn($productPriceRepository);

        $job = $this->createMock(Job::class);
        $job
            ->expects(self::any())
            ->method('getId')
            ->willReturn(123);

        $jobRunner = $this->createMock(JobRunner::class);
        $jobRunner
            ->expects(self::any())
            ->method('createDelayed')
            ->willReturnCallback(function ($name, $closure) use ($jobRunner, $job) {
                return $closure($jobRunner, $job);
            });

        $this->dataStorage
            ->expects(self::exactly(3))
            ->method('insertMultipleRequests')
            ->withConsecutive(
                [123, [], [1]],
                [123, [], [2]],
                [123, [], [3]]
            );

        $this->jobRunner
            ->expects(self::once())
            ->method('runUniqueByMessage')
            ->willReturnCallback(function ($message, $closure) use ($jobRunner, $job) {
                return $closure($jobRunner, $job);
            });

        $producer
            ->expects(self::once())
            ->method('send')
            ->with(
                ReindexRequestItemProductsByRelatedJobIdTopic::getName(),
                ['relatedJobId' => 123, 'indexationFieldsGroups' => ['pricing']]
            );

        $processor->setProductsBatchSize(1);
        self::assertSame(
            $processor::ACK,
            $processor->process($this->getMessage($body), $this->getSession())
        );
    }

    private function getMessage(array $body): MessageInterface
    {
        $message = $this->createMock(MessageInterface::class);
        $message
            ->expects(self::once())
            ->method('getBody')
            ->willReturn($body);

        return $message;
    }

    private function getSession(): SessionInterface
    {
        return $this->createMock(SessionInterface::class);
    }
}
