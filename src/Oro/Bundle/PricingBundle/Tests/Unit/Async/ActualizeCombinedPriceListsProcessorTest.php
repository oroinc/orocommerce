<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\MessageQueueBundle\Client\BufferedMessageProducer;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\PricingBundle\Async\ActualizeCombinedPriceListsProcessor;
use Oro\Bundle\PricingBundle\Async\Topic\ActualizeCombinedPriceListTopic;
use Oro\Bundle\PricingBundle\Async\Topic\CombineSingleCombinedPriceListPricesTopic;
use Oro\Bundle\PricingBundle\Async\Topic\RunCombinedPriceListPostProcessingStepsTopic;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListBuildActivity;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListBuildActivityRepository;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Job\DependentJobContext;
use Oro\Component\MessageQueue\Job\DependentJobService;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ActualizeCombinedPriceListsProcessorTest extends TestCase
{
    use EntityTrait;

    private LoggerInterface|MockObject $logger;
    private JobRunner|MockObject $jobRunner;
    private MessageProducerInterface|MockObject $producer;
    private DependentJobService|MockObject $dependentJob;
    private ManagerRegistry|MockObject $doctrine;
    private ActualizeCombinedPriceListsProcessor $processor;

    #[\Override]
    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->jobRunner = $this->createMock(JobRunner::class);
        $this->producer = $this->createMock(MessageProducerInterface::class);
        $this->dependentJob = $this->createMock(DependentJobService::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->processor = new ActualizeCombinedPriceListsProcessor(
            $this->doctrine,
            $this->producer,
            $this->jobRunner,
            $this->dependentJob
        );
        $this->processor->setLogger($this->logger);
    }

    public function testGetSubscribedTopics(): void
    {
        self::assertSame(
            [ActualizeCombinedPriceListTopic::getName()],
            ActualizeCombinedPriceListsProcessor::getSubscribedTopics()
        );
    }

    public function testProcessUnexpectedException(): void
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::any())
            ->method('getBody')
            ->willReturn([
                'cpl' => [$this->getEntity(CombinedPriceList::class, ['id' => 1])]
            ]);

        $e = new \Exception();
        $this->jobRunner->expects(self::once())
            ->method('runUniqueByMessage')
            ->willThrowException($e);

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Combined Price Lists build.',
                ['exception' => $e]
            );

        self::assertSame(
            $this->processor::REJECT,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
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

        $processor = new ActualizeCombinedPriceListsProcessor(
            $this->doctrine,
            $bufferedProducer,
            $this->jobRunner,
            $this->dependentJob
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

        $processor = new ActualizeCombinedPriceListsProcessor(
            $this->doctrine,
            $bufferedProducer,
            $this->jobRunner,
            $this->dependentJob
        );
        $processor->setLogger($this->logger);

        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::any())
            ->method('getBody')
            ->willReturn([
                'cpl' => [$this->getEntity(CombinedPriceList::class, ['id' => 1])]
            ]);

        $e = new \Exception('Test exception');
        $this->jobRunner->expects(self::once())
            ->method('runUniqueByMessage')
            ->willThrowException($e);

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Combined Price Lists build.',
                ['exception' => $e]
            );

        self::assertSame(
            $processor::REJECT,
            $processor->process($message, $this->createMock(SessionInterface::class))
        );
    }


    private function assertProcessCalls(
        ActualizeCombinedPriceListsProcessor $processor,
        MessageProducerInterface|MockObject $producer
    ): void {
        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 1, 'name' => 'test_cpl']);
        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::any())
            ->method('getBody')
            ->willReturn([
                'cpl' => [$cpl]
            ]);

        $rootJob = $this->createMock(Job::class);
        $rootJob->expects(self::any())
            ->method('getId')
            ->willReturn(4242);
        $job = $this->createMock(Job::class);
        $job->expects(self::any())
            ->method('getRootJob')
            ->willReturn($rootJob);
        $job->expects(self::once())
            ->method('getName')
            ->willReturn('oro_pricing.actualize_combined_price_lists');
        $childJob = $this->createMock(Job::class);
        $childJob->expects(self::any())
            ->method('getId')
            ->willReturn(42);
        $this->jobRunner->expects(self::once())
            ->method('runUniqueByMessage')
            ->willReturnCallback(
                function ($actualMessage, $closure) use ($job, $message) {
                    self::assertSame($actualMessage, $message);

                    return $closure($this->jobRunner, $job);
                }
            );
        $this->jobRunner->expects(self::once())
            ->method('createDelayed')
            ->with('oro_pricing.actualize_combined_price_lists:cpl:test_cpl')
            ->willReturnCallback(function ($name, $closure) use ($childJob) {
                return $closure($this->jobRunner, $childJob);
            });

        $dependentContext = $this->createMock(DependentJobContext::class);
        $dependentContext->expects(self::once())
            ->method('addDependentJob')
            ->with(
                RunCombinedPriceListPostProcessingStepsTopic::getName(),
                [
                    'relatedJobId' => 4242,
                    'cpls' => [1]
                ]
            );
        $this->dependentJob->expects(self::once())
            ->method('createDependentJobContext')
            ->with($rootJob)
            ->willReturn($dependentContext);
        $this->dependentJob->expects(self::once())
            ->method('saveDependentJob')
            ->with($dependentContext);

        $producer->expects(self::once())
            ->method('send')
            ->with(
                CombineSingleCombinedPriceListPricesTopic::getName(),
                [
                    'jobId' => 42,
                    'cpl' => 1
                ]
            );

        $cplBuildActivityRepo = $this->createMock(CombinedPriceListBuildActivityRepository::class);
        $cplBuildActivityRepo->expects(self::once())
            ->method('addBuildActivities')
            ->with([$cpl], 4242);

        $this->doctrine->expects(self::any())
            ->method('getRepository')
            ->with(CombinedPriceListBuildActivity::class)
            ->willReturn($cplBuildActivityRepo);

        self::assertSame(
            $processor::ACK,
            $processor->process($message, $this->createMock(SessionInterface::class))
        );
    }
}
