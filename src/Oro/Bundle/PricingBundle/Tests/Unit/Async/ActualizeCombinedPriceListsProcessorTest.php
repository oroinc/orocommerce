<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Async;

use Doctrine\Persistence\ManagerRegistry;
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
use Psr\Log\LoggerInterface;

class ActualizeCombinedPriceListsProcessorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var JobRunner|MockObject
     */
    private $jobRunner;

    /**
     * @var MessageProducerInterface|MockObject
     */
    private $producer;

    /**
     * @var DependentJobService|MockObject
     */
    private $dependentJob;

    /**
     * @var ManagerRegistry
     */
    private $doctrine;

    /**
     * @var ActualizeCombinedPriceListsProcessor
     */
    private $processor;

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

    public function testGetSubscribedTopics()
    {
        $this->assertEquals(
            [ActualizeCombinedPriceListTopic::getName()],
            ActualizeCombinedPriceListsProcessor::getSubscribedTopics()
        );
    }

    public function testProcessUnexpectedException()
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn([
                'cpl' => [$this->getEntity(CombinedPriceList::class, ['id' => 1])]
            ]);

        $e = new \Exception();
        $this->jobRunner->expects($this->once())
            ->method('runUniqueByMessage')
            ->willThrowException($e);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Combined Price Lists build.',
                ['exception' => $e]
            );

        $this->assertEquals(
            $this->processor::REJECT,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );
    }

    public function testProcess()
    {
        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 1]);
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn([
                'cpl' => [$cpl]
            ]);

        $rootJob = $this->createMock(Job::class);
        $rootJob->expects($this->any())
            ->method('getId')
            ->willReturn(4242);
        $job = $this->createMock(Job::class);
        $job->expects($this->any())
            ->method('getRootJob')
            ->willReturn($rootJob);
        $childJob = $this->createMock(Job::class);
        $childJob->expects($this->any())
            ->method('getId')
            ->willReturn(42);
        $this->jobRunner->expects($this->once())
            ->method('runUniqueByMessage')
            ->willReturnCallback(
                function ($actualMessage, $closure) use ($job, $message) {
                    $this->assertSame($actualMessage, $message);

                    return $closure($this->jobRunner, $job);
                }
            );
        $this->jobRunner->expects($this->once())
            ->method('createDelayed')
            ->willReturnCallback(function ($name, $closure) use ($childJob) {
                return $closure($this->jobRunner, $childJob);
            });

        $dependentContext = $this->createMock(DependentJobContext::class);
        $dependentContext->expects($this->once())
            ->method('addDependentJob')
            ->with(
                RunCombinedPriceListPostProcessingStepsTopic::getName(),
                [
                    'relatedJobId' => 4242,
                    'cpls' => [1]
                ]
            );
        $this->dependentJob->expects($this->once())
            ->method('createDependentJobContext')
            ->with($rootJob)
            ->willReturn($dependentContext);
        $this->dependentJob->expects($this->once())
            ->method('saveDependentJob')
            ->with($dependentContext);

        $this->producer->expects($this->once())
            ->method('send')
            ->with(
                CombineSingleCombinedPriceListPricesTopic::getName(),
                [
                    'jobId' => 42,
                    'cpl' => 1
                ]
            );

        $cplBuildActivityRepo = $this->createMock(CombinedPriceListBuildActivityRepository::class);
        $cplBuildActivityRepo->expects($this->once())
            ->method('addBuildActivities')
            ->with([$cpl], 4242);

        $this->doctrine->expects($this->any())
            ->method('getRepository')
            ->with(CombinedPriceListBuildActivity::class)
            ->willReturn($cplBuildActivityRepo);

        $this->assertEquals(
            $this->processor::ACK,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );
    }
}
