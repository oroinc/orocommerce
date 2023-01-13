<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Async;

use Oro\Bundle\PricingBundle\Async\CombinedPriceListPostProcessingStepsProcessor;
use Oro\Bundle\PricingBundle\Async\Topic\RunCombinedPriceListPostProcessingStepsTopic;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListGarbageCollector;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTriggerHandler;
use Oro\Bundle\ProductBundle\Async\Topic\ReindexRequestItemProductsByRelatedJobIdTopic;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CombinedPriceListPostProcessingStepsProcessorTest extends TestCase
{
    private CombinedPriceListGarbageCollector|MockObject $garbageCollector;
    private CombinedPriceListTriggerHandler|MockObject $triggerHandler;
    private MessageProducerInterface|MockObject $producer;
    private CombinedPriceListPostProcessingStepsProcessor $processor;

    protected function setUp(): void
    {
        $this->garbageCollector = $this->createMock(CombinedPriceListGarbageCollector::class);
        $this->triggerHandler = $this->createMock(CombinedPriceListTriggerHandler::class);
        $this->producer = $this->createMock(MessageProducerInterface::class);

        $this->processor = new CombinedPriceListPostProcessingStepsProcessor(
            $this->garbageCollector,
            $this->triggerHandler,
            $this->producer
        );
    }

    public function testGetSubscribedTopics()
    {
        $this->assertEquals(
            [RunCombinedPriceListPostProcessingStepsTopic::getName()],
            CombinedPriceListPostProcessingStepsProcessor::getSubscribedTopics()
        );
    }

    public function testProcess()
    {
        $jobId = 1;
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->once())
            ->method('getBody')
            ->willReturn([
                'relatedJobId' => $jobId,
                'cpls' => [1]
            ]);

        $this->triggerHandler->expects($this->once())
            ->method('startCollect')
            ->with($jobId);
        $this->triggerHandler->expects($this->once())
            ->method('commit');

        $this->garbageCollector
            ->expects($this->once())
            ->method('cleanCombinedPriceLists')
            ->with([1]);

        $this->producer->expects($this->once())
            ->method('send')
            ->with(
                ReindexRequestItemProductsByRelatedJobIdTopic::getName(),
                [
                    'relatedJobId' => $jobId,
                    'indexationFieldsGroups' => ['pricing']
                ]
            );

        $this->assertEquals(
            $this->processor::ACK,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );
    }

    public function testProcessException()
    {
        $jobId = 1;
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->once())
            ->method('getBody')
            ->willReturn([
                'relatedJobId' => $jobId,
                'cpls' => [1]
            ]);

        $e = new \Exception('Cpl GC error');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Combined Price Lists garbage collection.',
                [
                    'topic' => RunCombinedPriceListPostProcessingStepsTopic::getName(),
                    'exception' => $e
                ]
            );

        $this->processor->setLogger($logger);

        $this->triggerHandler->expects($this->once())
            ->method('startCollect')
            ->with($jobId);
        $this->triggerHandler->expects($this->never())
            ->method('commit');
        $this->triggerHandler->expects($this->once())
            ->method('rollback');

        $this->garbageCollector->expects($this->once())
            ->method('cleanCombinedPriceLists')
            ->willThrowException($e);

        $this->producer->expects($this->once())
            ->method('send')
            ->with(
                ReindexRequestItemProductsByRelatedJobIdTopic::getName(),
                [
                    'relatedJobId' => $jobId,
                    'indexationFieldsGroups' => ['pricing']
                ]
            );

        $this->assertEquals(
            $this->processor::ACK,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );
    }
}
