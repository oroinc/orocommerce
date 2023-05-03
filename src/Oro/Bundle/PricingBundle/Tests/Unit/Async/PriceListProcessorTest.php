<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\PricingBundle\Async\PriceListProcessor;
use Oro\Bundle\PricingBundle\Async\Topic\CombineSingleCombinedPriceListPricesTopic;
use Oro\Bundle\PricingBundle\Async\Topic\ResolveCombinedPriceByPriceListTopic;
use Oro\Bundle\PricingBundle\Async\Topic\RunCombinedPriceListPostProcessingStepsTopic;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListBuildActivity;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListBuildActivityRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToPriceListRepository;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListStatusHandlerInterface;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\DependentJobContext;
use Oro\Component\MessageQueue\Job\DependentJobService;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class PriceListProcessorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var ManagerRegistry|MockObject
     */
    private $doctrine;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /** @var CombinedPriceListStatusHandlerInterface|MockObject */
    private $statusHandler;

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

    /** @var PriceListProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->statusHandler = $this->createMock(CombinedPriceListStatusHandlerInterface::class);
        $this->jobRunner = $this->createMock(JobRunner::class);
        $this->producer = $this->createMock(MessageProducerInterface::class);
        $this->dependentJob = $this->createMock(DependentJobService::class);

        $this->processor = new PriceListProcessor(
            $this->doctrine,
            $this->statusHandler,
            $this->producer,
            $this->jobRunner,
            $this->dependentJob
        );
        $this->processor->setLogger($this->logger);
    }

    /**
     * @param mixed $body
     *
     * @return MessageInterface
     */
    private function getMessage($body): MessageInterface
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->once())
            ->method('getBody')
            ->willReturn($body);

        return $message;
    }

    private function getSession(): SessionInterface
    {
        return $this->createMock(SessionInterface::class);
    }

    public function testGetSubscribedTopics()
    {
        $this->assertEquals(
            [ResolveCombinedPriceByPriceListTopic::getName()],
            PriceListProcessor::getSubscribedTopics()
        );
    }

    public function testProcessException()
    {
        $body = ['product' => [1 => [2]]];

        $exception = new \Exception('Some error');
        $this->jobRunner->expects($this->once())
            ->method('runUniqueByMessage')
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Price Lists build.',
                ['exception' => $exception]
            );

        $this->assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcess()
    {
        $priceListId = 1;
        $productIds = [2];
        $body = ['product' => [$priceListId => $productIds]];

        $cpl1 = $this->getEntity(CombinedPriceList::class, ['id' => 10]);
        $cpl2 = $this->getEntity(CombinedPriceList::class, ['id' => 20]);

        $cpl2plRepo = $this->createMock(CombinedPriceListToPriceListRepository::class);
        $cplBuildActivityRepo = $this->createMock(CombinedPriceListBuildActivityRepository::class);
        $this->doctrine->expects($this->any())
            ->method('getRepository')
            ->willReturnMap(
                [
                    [CombinedPriceListToPriceList::class, null, $cpl2plRepo],
                    [CombinedPriceListBuildActivity::class, null, $cplBuildActivityRepo]
                ]
            );
        $cpl2plRepo->expects($this->once())
            ->method('getCombinedPriceListsByActualPriceLists')
            ->with([$priceListId])
            ->willReturn([$cpl1, $cpl2]);
        $this->statusHandler->expects($this->exactly(2))
            ->method('isReadyForBuild')
            ->withConsecutive([$cpl1], [$cpl2])
            ->willReturnOnConsecutiveCalls(true, false);

        $cpl2plRepo->expects($this->once())
            ->method('getPriceListIdsByCpls')
            ->with([$cpl1])
            ->willReturn([$priceListId]);

        $rootJob = $this->createMock(Job::class);
        $rootJob->expects($this->any())
            ->method('getId')
            ->willReturn(10);
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
                function ($message, $closure) use ($job) {
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
                ['relatedJobId' => 10, 'cpls' => [$cpl1->getId()]]
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
                    'cpl' => $cpl1->getId(),
                    'products' => $productIds,
                    'jobId' => 42
                ]
            );

        $cplBuildActivityRepo->expects($this->once())
            ->method('addBuildActivities')
            ->with([$cpl1], 10);

        $this->assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcessBatchedProducts()
    {
        $priceListId = 1;
        $productIds = [2, 3];
        $body = ['product' => [$priceListId => $productIds]];
        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 10]);

        $cpl2plRepo = $this->createMock(CombinedPriceListToPriceListRepository::class);
        $cplBuildActivityRepo = $this->createMock(CombinedPriceListBuildActivityRepository::class);
        $this->doctrine->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [CombinedPriceListToPriceList::class, null, $cpl2plRepo],
                [CombinedPriceListBuildActivity::class, null, $cplBuildActivityRepo]
            ]);
        $cpl2plRepo->expects($this->once())
            ->method('getCombinedPriceListsByActualPriceLists')
            ->with([$priceListId])
            ->willReturn([$cpl]);
        $this->statusHandler->expects($this->once())
            ->method('isReadyForBuild')
            ->with($cpl)
            ->willReturn(true);
        $cpl2plRepo->expects($this->once())
            ->method('getPriceListIdsByCpls')
            ->with([$cpl])
            ->willReturn([$priceListId]);

        $rootJob = $this->createMock(Job::class);
        $rootJob->expects($this->any())
            ->method('getId')
            ->willReturn(10);
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
            ->willReturnCallback(function ($message, $closure) use ($job) {
                return $closure($this->jobRunner, $job);
            });
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
                ['relatedJobId' => 10, 'cpls' => [10]]
            );
        $this->dependentJob->expects($this->once())
            ->method('createDependentJobContext')
            ->with($rootJob)
            ->willReturn($dependentContext);
        $this->dependentJob->expects($this->once())
            ->method('saveDependentJob')
            ->with($dependentContext);

        $this->producer->expects($this->exactly(2))
            ->method('send')
            ->withConsecutive(
                [
                    CombineSingleCombinedPriceListPricesTopic::getName(),
                    [
                        'cpl' => $cpl->getId(),
                        'products' => [2],
                        'jobId' => 42
                    ]
                ],
                [
                    CombineSingleCombinedPriceListPricesTopic::getName(),
                    [
                        'cpl' => $cpl->getId(),
                        'products' => [3],
                        'jobId' => 42
                    ]
                ]
            );

        $cplBuildActivityRepo->expects($this->once())
            ->method('addBuildActivities')
            ->with([$cpl], 10);

        $this->processor->setProductsBatchSize(1);
        $this->assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }
}
