<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\PricingBundle\Async\Topic\CombineSingleCombinedPriceListPricesTopic;
use Oro\Bundle\PricingBundle\Async\Topic\ResolveCombinedPriceByVersionedPriceListTopic;
use Oro\Bundle\PricingBundle\Async\Topic\RunCombinedPriceListPostProcessingStepsTopic;
use Oro\Bundle\PricingBundle\Async\VersionedPriceListProcessor;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListBuildActivity;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListBuildActivityRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToPriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListStatusHandlerInterface;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
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

class VersionedPriceListProcessorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ManagerRegistry|MockObject */
    private $doctrine;

    /** @var LoggerInterface|MockObject */
    private $logger;

    /** @var CombinedPriceListStatusHandlerInterface|MockObject */
    private $statusHandler;

    /** @var JobRunner|MockObject */
    private $jobRunner;

    /** @var MessageProducerInterface|MockObject */
    private $producer;

    /** @var DependentJobService|MockObject */
    private $dependentJob;

    /** @var ShardManager|MockObject */
    private $shardManager;

    /** @var VersionedPriceListProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->statusHandler = $this->createMock(CombinedPriceListStatusHandlerInterface::class);
        $this->jobRunner = $this->createMock(JobRunner::class);
        $this->producer = $this->createMock(MessageProducerInterface::class);
        $this->dependentJob = $this->createMock(DependentJobService::class);
        $this->shardManager = $this->createMock(ShardManager::class);

        $this->processor = new VersionedPriceListProcessor(
            $this->doctrine,
            $this->jobRunner,
            $this->dependentJob,
            $this->producer,
            $this->statusHandler,
            $this->shardManager
        );
        $this->processor->setLogger($this->logger);
    }

    private function getMessage(array $body): MessageInterface
    {
        $message = $this->createMock(MessageInterface::class);
        $message
            ->expects($this->once())
            ->method('getBody')
            ->willReturn($body);

        return $message;
    }

    private function getSession(): SessionInterface
    {
        return $this->createMock(SessionInterface::class);
    }

    public function testGetSubscribedTopics(): void
    {
        $this->assertEquals(
            [ResolveCombinedPriceByVersionedPriceListTopic::getName()],
            VersionedPriceListProcessor::getSubscribedTopics()
        );
    }

    public function testProcessException(): void
    {
        $body = ['priceLists' => [1], 'version' => 1, 'cpls' => [1]];
        $this->assertRepositories();

        $exception = new \Exception('Some error');
        $this->jobRunner
            ->expects($this->once())
            ->method('runUniqueByMessage')
            ->willThrowException($exception);

        $this->logger
            ->expects($this->once())
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

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcess(): void
    {
        $priceListIds = [1];
        $version = 1;
        $body = ['priceLists' => $priceListIds, 'version' => $version];
        $productIds = [2];

        [
            $productPriceRepository,
            $combinedPriceListToPriceListRepository,
            $combinedPriceListBuildActivityRepository
        ] = $this->assertRepositories();

        $combinedPriceList1 = $this->getEntity(CombinedPriceList::class, ['id' => 10]);
        $combinedPriceList2 = $this->getEntity(CombinedPriceList::class, ['id' => 20]);

        $this->doctrine
            ->expects($this->any())
            ->method('getRepository')
            ->willReturnMap(
                [
                    [CombinedPriceListToPriceList::class, null, $combinedPriceListToPriceListRepository],
                    [CombinedPriceListBuildActivity::class, null, $combinedPriceListBuildActivityRepository]
                ]
            );

        $this->statusHandler
            ->expects($this->exactly(2))
            ->method('isReadyForBuild')
            ->withConsecutive([$combinedPriceList1], [$combinedPriceList2])
            ->willReturnOnConsecutiveCalls(true, false);

        $productPriceRepository
            ->expects($this->once())
            ->method('getProductsByPriceListAndVersion')
            ->willReturn([$productIds]);

        $combinedPriceListToPriceListRepository
            ->expects($this->once())
            ->method('getCombinedPriceListsByActualPriceLists')
            ->with($priceListIds)
            ->willReturn([$combinedPriceList1, $combinedPriceList2]);
        $combinedPriceListToPriceListRepository
            ->expects($this->once())
            ->method('getPriceListIdsByCpls')
            ->with([$combinedPriceList1])
            ->willReturn($priceListIds);
        $combinedPriceListBuildActivityRepository
            ->expects($this->once())
            ->method('addBuildActivities')
            ->with([$combinedPriceList1], 10);

        $rootJob = $this->createMock(Job::class);
        $rootJob
            ->expects($this->any())
            ->method('getId')
            ->willReturn(10);

        $job = $this->createMock(Job::class);
        $job
            ->expects($this->any())
            ->method('getRootJob')
            ->willReturn($rootJob);

        $childJob = $this->createMock(Job::class);
        $childJob
            ->expects($this->any())
            ->method('getId')
            ->willReturn(42);

        $this->jobRunner
            ->expects($this->once())
            ->method('runUniqueByMessage')
            ->willReturnCallback(fn ($message, $closure) => $closure($this->jobRunner, $job));
        $this->jobRunner
            ->expects($this->once())
            ->method('createDelayed')
            ->willReturnCallback(fn ($name, $closure) => $closure($this->jobRunner, $childJob));

        $dependentContext = $this->createMock(DependentJobContext::class);
        $dependentContext->expects($this->once())
            ->method('addDependentJob')
            ->with(RunCombinedPriceListPostProcessingStepsTopic::getName(), ['relatedJobId' => 10, 'cpls' => [10]]);

        $this->dependentJob
            ->expects($this->once())
            ->method('createDependentJobContext')
            ->with($rootJob)
            ->willReturn($dependentContext);
        $this->dependentJob
            ->expects($this->once())
            ->method('saveDependentJob')
            ->with($dependentContext);

        $this->producer
            ->expects($this->once())
            ->method('send')
            ->with(
                CombineSingleCombinedPriceListPricesTopic::getName(),
                [
                    'cpl' => $combinedPriceList1->getId(),
                    'products' => $productIds,
                    'jobId' => 42
                ]
            );

        $this->assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessBatchedProducts()
    {
        $version = 1;
        $priceListIds = [1];
        $productIds = [[2], [3]];
        $body = ['priceLists' => $priceListIds, 'version' => $version];
        $combinedPriceList = $this->getEntity(CombinedPriceList::class, ['id' => 10]);

        [
            $productPriceRepository,
            $combinedPriceListToPriceListRepository,
            $combinedPriceListBuildActivityRepository
        ] = $this->assertRepositories();

        $this->statusHandler
            ->expects($this->once())
            ->method('isReadyForBuild')
            ->with($combinedPriceList)
            ->willReturn(true);

        $productPriceRepository
            ->expects($this->once())
            ->method('getProductsByPriceListAndVersion')
            ->willReturn($productIds);

        $combinedPriceListToPriceListRepository
            ->expects($this->once())
            ->method('getCombinedPriceListsByActualPriceLists')
            ->with($priceListIds)
            ->willReturn([$combinedPriceList]);
        $combinedPriceListToPriceListRepository
            ->expects($this->once())
            ->method('getPriceListIdsByCpls')
            ->with([$combinedPriceList])
            ->willReturn($priceListIds);
        $combinedPriceListBuildActivityRepository
            ->expects($this->once())
            ->method('addBuildActivities')
            ->with([$combinedPriceList], 10);


        $rootJob = $this->createMock(Job::class);
        $rootJob
            ->expects($this->any())
            ->method('getId')
            ->willReturn(10);

        $job = $this->createMock(Job::class);
        $job
            ->expects($this->any())
            ->method('getRootJob')
            ->willReturn($rootJob);

        $childJob = $this->createMock(Job::class);
        $childJob
            ->expects($this->any())
            ->method('getId')
            ->willReturn(42);

        $this->jobRunner
            ->expects($this->once())
            ->method('runUniqueByMessage')
            ->willReturnCallback(fn ($message, $closure) => $closure($this->jobRunner, $job));
        $this->jobRunner
            ->expects($this->once())
            ->method('createDelayed')
            ->willReturnCallback(fn ($name, $closure) => $closure($this->jobRunner, $childJob));

        $dependentContext = $this->createMock(DependentJobContext::class);
        $dependentContext
            ->expects($this->once())
            ->method('addDependentJob')
            ->with(RunCombinedPriceListPostProcessingStepsTopic::getName(), ['relatedJobId' => 10, 'cpls' => [10]]);

        $this->dependentJob
            ->expects($this->once())
            ->method('createDependentJobContext')
            ->with($rootJob)
            ->willReturn($dependentContext);
        $this->dependentJob
            ->expects($this->once())
            ->method('saveDependentJob')
            ->with($dependentContext);

        $this->producer
            ->expects($this->exactly(2))
            ->method('send')
            ->withConsecutive(
                [
                    CombineSingleCombinedPriceListPricesTopic::getName(),
                    [
                        'cpl' => $combinedPriceList->getId(),
                        'products' => [2],
                        'jobId' => 42
                    ]
                ],
                [
                    CombineSingleCombinedPriceListPricesTopic::getName(),
                    [
                        'cpl' => $combinedPriceList->getId(),
                        'products' => [3],
                        'jobId' => 42
                    ]
                ]
            );

        $this->processor->setProductsBatchSize(1);
        $this->assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    private function assertRepositories(): array
    {
        $productPriceRepository = $this->createMock(ProductPriceRepository::class);
        $combinedPriceListToPriceListRepository = $this->createMock(CombinedPriceListToPriceListRepository::class);
        $combinedPriceListBuildActivityRepository = $this->createMock(CombinedPriceListBuildActivityRepository::class);

        $this->doctrine
            ->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [ProductPrice::class, null, $productPriceRepository],
                [CombinedPriceListToPriceList::class, null, $combinedPriceListToPriceListRepository],
                [CombinedPriceListBuildActivity::class, null, $combinedPriceListBuildActivityRepository],
            ]);

        return [
            $productPriceRepository,
            $combinedPriceListToPriceListRepository,
            $combinedPriceListBuildActivityRepository
        ];
    }
}
