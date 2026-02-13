<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Async;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\MessageQueueBundle\Client\BufferedMessageProducer;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\PricingBundle\Async\CombinedPriceListProcessor;
use Oro\Bundle\PricingBundle\Async\Topic\CombineSingleCombinedPriceListPricesTopic;
use Oro\Bundle\PricingBundle\Async\Topic\MassRebuildCombinedPriceListsTopic;
use Oro\Bundle\PricingBundle\Async\Topic\RunCombinedPriceListPostProcessingStepsTopic;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Provider\CombinedPriceListAssociationsProvider;
use Oro\Bundle\PricingBundle\Provider\PriceListSequenceMember;
use Oro\Bundle\WebsiteBundle\Entity\Website;
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

class CombinedPriceListProcessorTest extends TestCase
{
    use EntityTrait;

    private LoggerInterface|MockObject $logger;
    private CombinedPriceListAssociationsProvider|MockObject $cplAssociationsProvider;
    private JobRunner|MockObject $jobRunner;
    private MessageProducerInterface|MockObject $producer;
    private DependentJobService|MockObject $dependentJob;
    private ManagerRegistry|MockObject $doctrine;
    private CombinedPriceListProcessor $processor;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->cplAssociationsProvider = $this->createMock(CombinedPriceListAssociationsProvider::class);
        $this->jobRunner = $this->createMock(JobRunner::class);
        $this->producer = $this->createMock(MessageProducerInterface::class);
        $this->dependentJob = $this->createMock(DependentJobService::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->processor = new CombinedPriceListProcessor(
            $this->cplAssociationsProvider,
            $this->producer,
            $this->jobRunner,
            $this->dependentJob,
            $this->doctrine
        );
        $this->processor->setLogger($this->logger);
    }

    public function testGetSubscribedTopics(): void
    {
        self::assertSame(
            [MassRebuildCombinedPriceListsTopic::getName()],
            CombinedPriceListProcessor::getSubscribedTopics()
        );
    }

    public function testProcessUnexpectedException(): void
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::any())
            ->method('getBody')
            ->willReturn([
                'assignments' => [['force' => true, 'website' => null, 'customer' => null, 'customerGroup' => null]]
            ]);

        $associations = [
            [
                'collection' => [new PriceListSequenceMember($this->getEntity(PriceList::class, ['id' => 10]), false)],
                'assign_to' => ['config' => true]
            ]
        ];
        $this->cplAssociationsProvider->expects(self::once())
            ->method('getCombinedPriceListsWithAssociations')
            ->willReturn($associations);

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

    /**
     * @dataProvider messageDataProvider
     */
    public function testProcess(array $body, ?Website $website, ?object $targetEntity, bool $isForce): void
    {
        $this->assertProcessCalls($this->processor, $this->producer, $body, $website, $targetEntity, $isForce);
    }

    public function testProcessWithBufferedProducer(): void
    {
        $bufferedProducer = $this->createMock(BufferedMessageProducer::class);

        $bufferedProducer->expects(self::once())
            ->method('disableBuffering');
        $bufferedProducer->expects(self::once())
            ->method('enableBuffering');

        $processor = new CombinedPriceListProcessor(
            $this->cplAssociationsProvider,
            $bufferedProducer,
            $this->jobRunner,
            $this->dependentJob,
            $this->doctrine
        );
        $processor->setLogger($this->logger);

        $body = [
            'assignments' => [['force' => true, 'website' => null, 'customer' => null, 'customerGroup' => null]]
        ];

        $this->assertProcessCalls($processor, $bufferedProducer, $body, null, null, true);
    }

    public function testProcessWithBufferedProducerEnsuresEnableBufferingOnException(): void
    {
        $bufferedProducer = $this->createMock(BufferedMessageProducer::class);
        $bufferedProducer->expects(self::once())
            ->method('disableBuffering');
        $bufferedProducer->expects(self::once())
            ->method('enableBuffering');

        $processor = new CombinedPriceListProcessor(
            $this->cplAssociationsProvider,
            $bufferedProducer,
            $this->jobRunner,
            $this->dependentJob,
            $this->doctrine
        );
        $processor->setLogger($this->logger);

        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::any())
            ->method('getBody')
            ->willReturn([
                'assignments' => [['force' => true, 'website' => null, 'customer' => null, 'customerGroup' => null]]
            ]);

        $associations = [
            [
                'collection' => [new PriceListSequenceMember($this->getEntity(PriceList::class, ['id' => 10]), false)],
                'assign_to' => ['config' => true]
            ]
        ];
        $this->cplAssociationsProvider->expects(self::once())
            ->method('getCombinedPriceListsWithAssociations')
            ->willReturn($associations);

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
        CombinedPriceListProcessor $processor,
        MessageProducerInterface|MockObject $producer,
        array $body,
        ?Website $website,
        ?object $targetEntity,
        bool $isForce
    ): void {
        $this->assertReference();
        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::any())
            ->method('getBody')
            ->willReturn($body);

        $association = [
            'collection' => [new PriceListSequenceMember($this->getEntity(PriceList::class, ['id' => 10]), false)],
            'assign_to' => ['config' => true]
        ];

        $this->cplAssociationsProvider->expects(self::once())
            ->method('getCombinedPriceListsWithAssociations')
            ->with($isForce, $website, $targetEntity)
            ->willReturn([$association]);

        $rootJob = $this->createMock(Job::class);
        $rootJob->expects(self::any())
            ->method('getId')
            ->willReturn(4242);
        $job = $this->createMock(Job::class);
        $job->expects(self::any())
            ->method('getRootJob')
            ->willReturn($rootJob);
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
            ->willReturnCallback(function ($name, $closure) use ($childJob) {
                return $closure($this->jobRunner, $childJob);
            });

        $dependentContext = $this->createMock(DependentJobContext::class);
        $dependentContext->expects(self::once())
            ->method('addDependentJob')
            ->with(RunCombinedPriceListPostProcessingStepsTopic::getName(), ['relatedJobId' => 4242]);
        $this->dependentJob->expects(self::once())
            ->method('createDependentJobContext')
            ->with($rootJob)
            ->willReturn($dependentContext);
        $this->dependentJob->expects(self::once())
            ->method('saveDependentJob')
            ->with($dependentContext);

        $producer
            ->expects(self::once())
            ->method('send')
            ->with(
                CombineSingleCombinedPriceListPricesTopic::getName(),
                self::callback(function ($args) use ($association) {
                    self::assertEquals($association['collection'], $args['collection']);
                    self::assertEquals($association['assign_to'], $args['assign_to']);
                    self::assertEquals(42, $args['jobId']);
                    self::assertIsInt($args['version']);

                    return true;
                })
            );

        self::assertSame(
            $processor::ACK,
            $processor->process($message, $this->createMock(SessionInterface::class))
        );
    }

    public function messageDataProvider(): \Generator
    {
        $website = $this->getEntity(Website::class, ['id' => 1]);
        $website2 = $this->getEntity(Website::class, ['id' => 2]);
        $customer = $this->getEntity(Customer::class, ['id' => 10]);
        $customerGroup = $this->getEntity(CustomerGroup::class, ['id' => 100]);

        yield 'full rebuild' => [
            [
                'assignments' => [['force' => true, 'website' => null, 'customer' => null, 'customerGroup' => null]]
            ],
            null,
            null,
            true
        ];

        yield 'per website' => [
            [
                'assignments' => [
                    ['force' => false, 'website' => $website, 'customer' => null, 'customerGroup' => null]
                ]
            ],
            $website,
            null,
            false
        ];

        yield 'per website2' => [
            [
                'assignments' => [
                    ['force' => false, 'website' => $website2, 'customer' => null, 'customerGroup' => null]
                ]
            ],
            $website2,
            null,
            false
        ];

        yield 'per website and customer' => [
            [
                'assignments' => [
                    ['force' => false, 'website' => $website, 'customer' => $customer, 'customerGroup' => null]
                ]
            ],
            $website,
            $customer,
            false
        ];

        yield 'per website and customer group' => [
            [
                'assignments' => [
                    ['force' => false, 'website' => $website, 'customer' => null, 'customerGroup' => $customerGroup]
                ]
            ],
            $website,
            $customerGroup,
            false
        ];
    }

    private function assertReference(): void
    {
        $manager = $this->createMock(EntityManagerInterface::class);
        $this->doctrine
            ->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($manager);

        $manager
            ->expects(self::any())
            ->method('getReference')
            ->willReturnCallback(function ($className, $value) {
                return $this->getEntity($className, ['id' => is_object($value) ? $value->getId() : $value]);
            });
    }
}
