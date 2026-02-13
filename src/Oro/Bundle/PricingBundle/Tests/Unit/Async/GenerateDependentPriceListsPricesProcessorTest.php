<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\MessageQueueBundle\Client\BufferedMessageProducer;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\PricingBundle\Async\GenerateDependentPriceListsPricesProcessor;
use Oro\Bundle\PricingBundle\Async\Topic\GenerateDependentPriceListPricesTopic;
use Oro\Bundle\PricingBundle\Async\Topic\GenerateSinglePriceListPricesByRulesTopic;
use Oro\Bundle\PricingBundle\Async\Topic\ResolveCombinedPriceByVersionedPriceListTopic;
use Oro\Bundle\PricingBundle\Async\Topic\ResolveVersionedFlatPriceTopic;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Oro\Bundle\PricingBundle\Provider\DependentPriceListProvider;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Job\DependentJobContext;
use Oro\Component\MessageQueue\Job\DependentJobService;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class GenerateDependentPriceListsPricesProcessorTest extends TestCase
{
    private const DEFAULT_DEPENDENT_PLS = [10, 20];
    private const DEFAULT_VERSION = 100;
    private const DEFAULT_SOURCE_PL = 1;

    private ManagerRegistry|MockObject $doctrine;
    private DependentPriceListProvider|MockObject $dependentPriceListProvider;
    private MessageProducerInterface|MockObject $messageProducer;
    private JobRunner|MockObject $jobRunner;
    private DependentJobService|MockObject $dependentJob;
    private FeatureChecker|MockObject $featureChecker;
    private LoggerInterface|MockObject $logger;
    private GenerateDependentPriceListsPricesProcessor $processor;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->dependentPriceListProvider = $this->createMock(DependentPriceListProvider::class);
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);
        $this->jobRunner = $this->createMock(JobRunner::class);
        $this->dependentJob = $this->createMock(DependentJobService::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->processor = new GenerateDependentPriceListsPricesProcessor(
            $this->doctrine,
            $this->dependentPriceListProvider,
            $this->messageProducer,
            $this->jobRunner,
            $this->dependentJob
        );
        $this->processor->setFeatureChecker($this->featureChecker);
        $this->processor->setLogger($this->logger);
    }

    public function testGetSubscribedTopics(): void
    {
        self::assertSame(
            [GenerateDependentPriceListPricesTopic::getName()],
            GenerateDependentPriceListsPricesProcessor::getSubscribedTopics()
        );
    }

    public function testProcessWithNullBody(): void
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::once())
            ->method('getBody')
            ->willReturn(null);

        self::assertSame(
            $this->processor::REJECT,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );
    }

    public function testProcessUnexpectedException(): void
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::any())
            ->method('getBody')
            ->willReturn([
                'sourcePriceListId' => self::DEFAULT_SOURCE_PL,
                'version' => self::DEFAULT_VERSION,
                'level' => 0,
                'productBatches' => $this->createGenerator([])
            ]);

        $e = new \Exception('Test exception');
        $this->dependentPriceListProvider->expects(self::once())
            ->method('getResolvedOrderedDependencies')
            ->willThrowException($e);

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Price Lists prices generation.',
                ['exception' => $e]
            );

        self::assertSame(
            $this->processor::REJECT,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );
    }

    public function testProcess(): void
    {
        $this->featureChecker->expects(self::exactly(2))
            ->method('isFeatureEnabled')
            ->willReturn(false);

        $this->dependentJob->expects(self::never())
            ->method('createDependentJobContext');

        $this->assertProcessCalls(
            $this->processor,
            $this->messageProducer,
            $this->createJob()
        );
    }

    public function testProcessWithNoDependenciesAllFeaturesDisabled(): void
    {
        $this->dependentJob->expects(self::never())
            ->method('createDependentJobContext');

        $messageBody = [
            'sourcePriceListId' => self::DEFAULT_SOURCE_PL,
            'version' => self::DEFAULT_VERSION,
            'level' => 0,
            'productBatches' => $this->createGenerator([[1, 2], [3, 4]])
        ];

        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::any())
            ->method('getBody')
            ->willReturn($messageBody);

        $this->dependentPriceListProvider->expects(self::once())
            ->method('getResolvedOrderedDependencies')
            ->with(self::DEFAULT_SOURCE_PL)
            ->willReturn([0 => [self::DEFAULT_SOURCE_PL]]);

        $priceListRepository = $this->createMock(PriceListRepository::class);
        $priceListRepository->expects(self::once())
            ->method('getActivePriceListIdsByIds')
            ->with([self::DEFAULT_SOURCE_PL])
            ->willReturnArgument(0);

        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(PriceList::class)
            ->willReturn($priceListRepository);

        $this->featureChecker->expects(self::any())
            ->method('isFeatureEnabled')
            ->willReturn(false);

        $this->messageProducer->expects(self::never())
            ->method('send');

        $this->processor->process($message, $this->createMock(SessionInterface::class));
    }

    public function testProcessWithNoDependenciesCplEnabled(): void
    {
        $this->dependentJob->expects(self::never())
            ->method('createDependentJobContext');

        $messageBody = [
            'sourcePriceListId' => self::DEFAULT_SOURCE_PL,
            'version' => self::DEFAULT_VERSION,
            'level' => 0,
            'productBatches' => $this->createGenerator([[1, 2], [3, 4]])
        ];

        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::any())
            ->method('getBody')
            ->willReturn($messageBody);

        $this->dependentPriceListProvider->expects(self::once())
            ->method('getResolvedOrderedDependencies')
            ->with(self::DEFAULT_SOURCE_PL)
            ->willReturn([0 => [self::DEFAULT_SOURCE_PL]]);

        $priceListRepository = $this->createMock(PriceListRepository::class);
        $priceListRepository->expects(self::once())
            ->method('getActivePriceListIdsByIds')
            ->with([self::DEFAULT_SOURCE_PL])
            ->willReturnArgument(0);

        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(PriceList::class)
            ->willReturn($priceListRepository);

        $this->featureChecker->expects(self::any())
            ->method('isFeatureEnabled')
            ->willReturnMap([
                ['oro_price_lists_combined', null, true],
                ['oro_price_lists_flat', null, false]
            ]);

        $this->messageProducer->expects(self::once())
            ->method('send')
            ->with(
                ResolveCombinedPriceByVersionedPriceListTopic::getName(),
                [
                    'version' => self::DEFAULT_VERSION,
                    'priceLists' => [self::DEFAULT_SOURCE_PL]
                ]
            );

        $this->processor->process($message, $this->createMock(SessionInterface::class));
    }

    public function testProcessWithNoDependenciesFlatEnabled(): void
    {
        $this->dependentJob->expects(self::never())
            ->method('createDependentJobContext');

        $messageBody = [
            'sourcePriceListId' => self::DEFAULT_SOURCE_PL,
            'version' => self::DEFAULT_VERSION,
            'level' => 0,
            'productBatches' => $this->createGenerator([[1, 2], [3, 4]])
        ];

        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::any())
            ->method('getBody')
            ->willReturn($messageBody);

        $this->dependentPriceListProvider->expects(self::once())
            ->method('getResolvedOrderedDependencies')
            ->with(self::DEFAULT_SOURCE_PL)
            ->willReturn([0 => [self::DEFAULT_SOURCE_PL]]);

        $priceListRepository = $this->createMock(PriceListRepository::class);
        $priceListRepository->expects(self::once())
            ->method('getActivePriceListIdsByIds')
            ->with([self::DEFAULT_SOURCE_PL])
            ->willReturnArgument(0);

        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(PriceList::class)
            ->willReturn($priceListRepository);

        $this->featureChecker->expects(self::any())
            ->method('isFeatureEnabled')
            ->willReturnMap([
                ['oro_price_lists_combined', null, false],
                ['oro_price_lists_flat', null, true]
            ]);

        $this->messageProducer->expects(self::once())
            ->method('send')
            ->with(
                ResolveVersionedFlatPriceTopic::getName(),
                [
                    'version' => self::DEFAULT_VERSION,
                    'priceLists' => [self::DEFAULT_SOURCE_PL]
                ]
            );

        $this->processor->process($message, $this->createMock(SessionInterface::class));
    }

    public function testProcessWithBufferedProducer(): void
    {
        $bufferedProducer = $this->createMock(BufferedMessageProducer::class);
        $bufferedProducer->expects(self::once())
            ->method('disableBuffering');
        $bufferedProducer->expects(self::once())
            ->method('enableBuffering');

        $processor = new GenerateDependentPriceListsPricesProcessor(
            $this->doctrine,
            $this->dependentPriceListProvider,
            $bufferedProducer,
            $this->jobRunner,
            $this->dependentJob
        );
        $processor->setFeatureChecker($this->featureChecker);
        $processor->setLogger($this->logger);

        $this->featureChecker->expects(self::exactly(2))
            ->method('isFeatureEnabled')
            ->willReturn(false);

        $this->dependentJob->expects(self::never())
            ->method('createDependentJobContext');

        $this->assertProcessCalls(
            $processor,
            $bufferedProducer,
            $this->createJob()
        );
    }

    public function testProcessWithBufferedProducerEnsuresEnableBufferingOnException(): void
    {
        $bufferedProducer = $this->createMock(BufferedMessageProducer::class);
        $bufferedProducer->expects(self::once())
            ->method('disableBuffering');
        $bufferedProducer->expects(self::once())
            ->method('enableBuffering');

        $processor = new GenerateDependentPriceListsPricesProcessor(
            $this->doctrine,
            $this->dependentPriceListProvider,
            $bufferedProducer,
            $this->jobRunner,
            $this->dependentJob
        );
        $processor->setFeatureChecker($this->featureChecker);
        $processor->setLogger($this->logger);

        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::any())
            ->method('getBody')
            ->willReturn([
                'sourcePriceListId' => self::DEFAULT_SOURCE_PL,
                'version' => self::DEFAULT_VERSION,
                'level' => 0,
                'productBatches' => $this->createGenerator([])
            ]);

        $e = new \Exception('Test exception');
        $this->dependentPriceListProvider->expects(self::once())
            ->method('getResolvedOrderedDependencies')
            ->willThrowException($e);

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Price Lists prices generation.',
                ['exception' => $e]
            );

        self::assertSame(
            $processor::REJECT,
            $processor->process($message, $this->createMock(SessionInterface::class))
        );
    }

    public function testProcessWithNextWave(): void
    {
        $waves = [
            0 => [self::DEFAULT_SOURCE_PL],
            1 => self::DEFAULT_DEPENDENT_PLS,
            2 => [30, 40],
            3 => [50, 60]
        ];

        $job = $this->createJob();

        $dependentContext = $this->createMock(DependentJobContext::class);
        $dependentContext->expects(self::once())
            ->method('addDependentJob')
            ->with(
                GenerateDependentPriceListPricesTopic::getName(),
                [
                    'level' => 1,
                    'sourcePriceListId' => self::DEFAULT_SOURCE_PL,
                    'version' => self::DEFAULT_VERSION,
                    'baseJobId' => 100,
                ]
            );

        $this->dependentJob->expects(self::once())
            ->method('createDependentJobContext')
            ->with($job->getRootJob())
            ->willReturn($dependentContext);

        $this->dependentJob->expects(self::once())
            ->method('saveDependentJob')
            ->with($dependentContext);

        $this->assertProcessCalls(
            $this->processor,
            $this->messageProducer,
            $job,
            $waves
        );
    }

    public function testProcessWithCombinedPriceListsFeature(): void
    {
        $this->featureChecker->expects(self::any())
            ->method('isFeatureEnabled')
            ->willReturnMap([
                ['oro_price_lists_combined', null, true],
                ['oro_price_lists_flat', null, false]
            ]);

        $dependentContext = $this->createMock(DependentJobContext::class);
        $dependentContext->expects(self::once())
            ->method('addDependentJob')
            ->with(
                ResolveCombinedPriceByVersionedPriceListTopic::getName(),
                [
                    'version' => self::DEFAULT_VERSION,
                    'priceLists' => array_merge([self::DEFAULT_SOURCE_PL], self::DEFAULT_DEPENDENT_PLS)
                ]
            );

        $job = $this->createJob();
        $this->dependentJob->expects(self::once())
            ->method('createDependentJobContext')
            ->with($job->getRootJob())
            ->willReturn($dependentContext);

        $this->dependentJob->expects(self::once())
            ->method('saveDependentJob')
            ->with($dependentContext);

        $this->assertProcessCalls(
            $this->processor,
            $this->messageProducer,
            $job
        );
    }

    public function testProcessWithFlatPriceListsFeature(): void
    {
        $this->featureChecker->expects(self::any())
            ->method('isFeatureEnabled')
            ->willReturnMap([
                ['oro_price_lists_combined', null, false],
                ['oro_price_lists_flat', null, true]
            ]);
        $job = $this->createJob();

        $dependentContext = $this->createMock(DependentJobContext::class);
        $dependentContext->expects(self::once())
            ->method('addDependentJob')
            ->with(
                ResolveVersionedFlatPriceTopic::getName(),
                [
                    'version' => self::DEFAULT_VERSION,
                    'priceLists' => array_merge([self::DEFAULT_SOURCE_PL], self::DEFAULT_DEPENDENT_PLS)
                ]
            );

        $this->dependentJob->expects(self::once())
            ->method('createDependentJobContext')
            ->with($job->getRootJob())
            ->willReturn($dependentContext);

        $this->dependentJob->expects(self::once())
            ->method('saveDependentJob')
            ->with($dependentContext);

        $this->assertProcessCalls(
            $this->processor,
            $this->messageProducer,
            $job
        );
    }

    public function testProcessWithNoActivePriceLists(): void
    {
        $waves = [
            self::DEFAULT_SOURCE_PL => self::DEFAULT_DEPENDENT_PLS
        ];

        $messageBody = [
            'sourcePriceListId' => self::DEFAULT_SOURCE_PL,
            'version' => self::DEFAULT_VERSION,
            'level' => 0,
            'productBatches' => $this->createGenerator([[1, 2]])
        ];

        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::any())
            ->method('getBody')
            ->willReturn($messageBody);

        $this->dependentPriceListProvider->expects(self::once())
            ->method('getResolvedOrderedDependencies')
            ->with(self::DEFAULT_SOURCE_PL)
            ->willReturn($waves);

        $priceListRepository = $this->createMock(PriceListRepository::class);
        $priceListRepository->expects(self::once())
            ->method('getActivePriceListIdsByIds')
            ->with(self::DEFAULT_DEPENDENT_PLS)
            ->willReturn([]);

        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(PriceList::class)
            ->willReturn($priceListRepository);

        $rootJob = $this->createMock(Job::class);
        $job = $this->createMock(Job::class);
        $job->expects(self::any())
            ->method('getRootJob')
            ->willReturn($rootJob);
        $job->expects(self::any())
            ->method('getName')
            ->willReturn('generate_dependent_prices');

        $childJob = $this->createMock(Job::class);
        $childJob->expects(self::any())
            ->method('getId')
            ->willReturn(200);

        $this->jobRunner->expects(self::once())
            ->method('runUniqueByMessage')
            ->willReturnCallback(
                function ($actualMessage, $closure) use ($job) {
                    return $closure($this->jobRunner, $job);
                }
            );

        $this->jobRunner->expects(self::exactly(2))
            ->method('createDelayed')
            ->willReturnCallback(function ($name, $closure) use ($childJob) {
                return $closure($this->jobRunner, $childJob);
            });

        $this->messageProducer->expects(self::exactly(2))
            ->method('send');

        $this->dependentJob->expects(self::never())
            ->method('createDependentJobContext');

        $this->dependentJob->expects(self::never())
            ->method('saveDependentJob');

        self::assertSame(
            $this->processor::ACK,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function assertProcessCalls(
        GenerateDependentPriceListsPricesProcessor $processor,
        MessageProducerInterface|MockObject $producer,
        Job|MockObject $job,
        array $waves = [0 => [self::DEFAULT_SOURCE_PL], 1 => self::DEFAULT_DEPENDENT_PLS]
    ): void {
        $messageBody = [
            'sourcePriceListId' => self::DEFAULT_SOURCE_PL,
            'version' => self::DEFAULT_VERSION,
            'level' => 0,
            'productBatches' => $this->createGenerator([[1, 2], [3, 4]])
        ];

        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::any())
            ->method('getBody')
            ->willReturn($messageBody);

        $this->dependentPriceListProvider->expects(self::once())
            ->method('getResolvedOrderedDependencies')
            ->with(self::DEFAULT_SOURCE_PL)
            ->willReturn($waves);

        if (!isset($waves[2])) {
            $priceListRepository = $this->createMock(PriceListRepository::class);
            $priceListRepository->expects(self::once())
                ->method('getActivePriceListIdsByIds')
                ->with(array_merge(...$waves))
                ->willReturnArgument(0);

            $this->doctrine->expects(self::once())
                ->method('getRepository')
                ->with(PriceList::class)
                ->willReturn($priceListRepository);
        } else {
            $this->doctrine->expects(self::never())
                ->method('getRepository')
                ->with(PriceList::class);
        }

        $childJob = $this->createMock(Job::class);
        $childJob->expects(self::any())
            ->method('getId')
            ->willReturn(200);

        $this->jobRunner->expects(self::once())
            ->method('runUniqueByMessage')
            ->willReturnCallback(
                function ($actualMessage, $closure) use ($message, $job) {
                    self::assertSame($actualMessage, $message);

                    return $closure($this->jobRunner, $job);
                }
            );

        $this->jobRunner->expects(self::exactly(4))
            ->method('createDelayed')
            ->willReturnCallback(function ($name, $closure) use ($childJob) {
                return $closure($this->jobRunner, $childJob);
            });

        $producer->expects(self::exactly(4))
            ->method('send')
            ->withConsecutive(
                [
                    GenerateSinglePriceListPricesByRulesTopic::getName(),
                    [
                        'priceListId' => self::DEFAULT_DEPENDENT_PLS[0],
                        'products' => [1, 2],
                        'version' => self::DEFAULT_VERSION,
                        'jobId' => 200
                    ]
                ],
                [
                    GenerateSinglePriceListPricesByRulesTopic::getName(),
                    [
                        'priceListId' => self::DEFAULT_DEPENDENT_PLS[1],
                        'products' => [1, 2],
                        'version' => self::DEFAULT_VERSION,
                        'jobId' => 200
                    ]
                ],
                [
                    GenerateSinglePriceListPricesByRulesTopic::getName(),
                    [
                        'priceListId' => self::DEFAULT_DEPENDENT_PLS[0],
                        'products' => [3, 4],
                        'version' => self::DEFAULT_VERSION,
                        'jobId' => 200
                    ]
                ],
                [
                    GenerateSinglePriceListPricesByRulesTopic::getName(),
                    [
                        'priceListId' => self::DEFAULT_DEPENDENT_PLS[1],
                        'products' => [3, 4],
                        'version' => self::DEFAULT_VERSION,
                        'jobId' => 200
                    ]
                ]
            );

        self::assertSame(
            $processor::ACK,
            $processor->process($message, $this->createMock(SessionInterface::class))
        );
    }

    private function createGenerator(array $data): \Generator
    {
        foreach ($data as $item) {
            yield $item;
        }
    }

    private function createJob(): MockObject|Job
    {
        $rootJob = $this->createMock(Job::class);
        $rootJob->expects(self::any())
            ->method('getId')
            ->willReturn(999);

        $job = $this->createMock(Job::class);
        $job->expects(self::any())
            ->method('getId')
            ->willReturn(100);
        $job->expects(self::any())
            ->method('getRootJob')
            ->willReturn($rootJob);
        $job->expects(self::any())
            ->method('getName')
            ->willReturn('generate_dependent_prices');

        return $job;
    }
}
