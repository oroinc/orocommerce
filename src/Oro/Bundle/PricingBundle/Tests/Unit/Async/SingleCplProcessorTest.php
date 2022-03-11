<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Async;

use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\PricingBundle\Async\SingleCplProcessor;
use Oro\Bundle\PricingBundle\Async\Topic\CombineSingleCombinedPriceListPricesTopic;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListsBuilderFacade;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListBuildActivity;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListBuildActivityRepository;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\CombinedPriceListsUpdateEvent;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListStatusHandlerInterface;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTriggerHandler;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class SingleCplProcessorTest extends TestCase
{
    use EntityTrait;

    private JobRunner|MockObject $jobRunner;
    private ManagerRegistry|MockObject $doctrine;
    private CombinedPriceListsBuilderFacade|MockObject $combinedPriceListsBuilderFacade;
    private CombinedPriceListTriggerHandler|MockObject $triggerHandler;
    private CombinedPriceListStatusHandlerInterface|MockObject $statusHandler;
    private EventDispatcherInterface|MockObject $dispatcher;
    private LoggerInterface|MockObject $logger;
    private SingleCplProcessor $processor;

    protected function setUp(): void
    {
        $this->jobRunner = $this->createMock(JobRunner::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->combinedPriceListsBuilderFacade = $this->createMock(CombinedPriceListsBuilderFacade::class);
        $this->indexationTriggerHandler = $this->createMock(CombinedPriceListTriggerHandler::class);
        $this->statusHandler = $this->createMock(CombinedPriceListStatusHandlerInterface::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->processor = new SingleCplProcessor(
            $this->jobRunner,
            $this->doctrine,
            $this->combinedPriceListsBuilderFacade,
            $this->indexationTriggerHandler,
            $this->statusHandler,
            $this->dispatcher
        );
        $this->processor->setLogger($this->logger);
    }

    public function testGetSubscribedTopics()
    {
        $this->assertEquals(
            [CombineSingleCombinedPriceListPricesTopic::getName()],
            SingleCplProcessor::getSubscribedTopics()
        );
    }

    public function testProcessUnexpectedException()
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn([
                'cpl' => $this->getEntity(CombinedPriceList::class, ['id' => 1]),
                'jobId' => 100,
                'products' => [],
                'assign_to' => []
            ]);

        $e = new \Exception();
        $rootJob = $this->createMock(Job::class);
        $rootJob->expects($this->any())
            ->method('getId')
            ->willReturn(42);
        $job = $this->createMock(Job::class);
        $job->expects($this->any())
            ->method('getRootJob')
            ->willReturn($rootJob);

        $this->jobRunner->expects($this->once())
            ->method('runDelayed')
            ->willReturnCallback(
                function ($ownerId, $closure) use ($job) {
                    return $closure($this->jobRunner, $job);
                }
            );

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($em);

        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects($this->once())
            ->method('rollback');
        $this->indexationTriggerHandler->expects($this->once())
            ->method('startCollect');
        $this->indexationTriggerHandler->expects($this->once())
            ->method('rollback');
        $this->statusHandler->expects($this->any())
            ->method('isReadyForBuild')
            ->willReturn(true);
        $this->combinedPriceListsBuilderFacade->expects($this->any())
            ->method('rebuild')
            ->willThrowException($e);
        $this->dispatcher->expects($this->never())
            ->method('dispatch');

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

    public function testProcessUnexpectedRetryableException()
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn([
                'cpl' => $this->getEntity(CombinedPriceList::class, ['id' => 1]),
                'jobId' => 100,
                'products' => [],
                'assign_to' => []
            ]);

        $e = $this->createMock(DeadlockException::class);
        $rootJob = $this->createMock(Job::class);
        $rootJob->expects($this->any())
            ->method('getId')
            ->willReturn(42);
        $job = $this->createMock(Job::class);
        $job->expects($this->any())
            ->method('getRootJob')
            ->willReturn($rootJob);

        $this->jobRunner->expects($this->once())
            ->method('runDelayed')
            ->willReturnCallback(
                function ($ownerId, $closure) use ($job) {
                    return $closure($this->jobRunner, $job);
                }
            );

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($em);

        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects($this->once())
            ->method('rollback');
        $this->indexationTriggerHandler->expects($this->once())
            ->method('startCollect');
        $this->indexationTriggerHandler->expects($this->once())
            ->method('rollback');
        $this->statusHandler->expects($this->any())
            ->method('isReadyForBuild')
            ->willReturn(true);
        $this->combinedPriceListsBuilderFacade->expects($this->any())
            ->method('rebuild')
            ->willThrowException($e);
        $this->dispatcher->expects($this->never())
            ->method('dispatch');

        $this->logger->expects($this->never())
            ->method('error');

        $this->expectException(DeadlockException::class);

        $this->processor->process($message, $this->createMock(SessionInterface::class));
    }

    /**
     * @dataProvider productsDataProvider
     */
    public function testProcess(array $products)
    {
        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 1]);
        $assignTo = ['config' => true];
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn([
                'cpl' => $cpl,
                'jobId' => 100,
                'products' => $products,
                'assign_to' => $assignTo
            ]);

        $rootJob = $this->createMock(Job::class);
        $rootJob->expects($this->any())
            ->method('getId')
            ->willReturn(42);
        $job = $this->createMock(Job::class);
        $job->expects($this->any())
            ->method('getRootJob')
            ->willReturn($rootJob);

        $this->jobRunner->expects($this->once())
            ->method('runDelayed')
            ->willReturnCallback(
                function ($ownerId, $closure) use ($job) {
                    return $closure($this->jobRunner, $job);
                }
            );

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($em);
        $this->assertActivityRecordsRemoval($products, $cpl);

        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects($this->once())
            ->method('commit');
        $em->expects($this->never())
            ->method('rollback');
        $this->indexationTriggerHandler->expects($this->once())
            ->method('startCollect');
        $this->indexationTriggerHandler->expects($this->once())
            ->method('commit');
        $this->indexationTriggerHandler->expects($this->never())
            ->method('rollback');
        $this->statusHandler->expects($this->once())
            ->method('isReadyForBuild')
            ->with($cpl)
            ->willReturn(true);
        $this->combinedPriceListsBuilderFacade->expects($this->once())
            ->method('rebuild')
            ->with([$cpl], $products);
        $this->combinedPriceListsBuilderFacade->expects($this->once())
            ->method('triggerProductIndexation')
            ->with($cpl, $assignTo, $products);
        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                new CombinedPriceListsUpdateEvent([$cpl->getId()]),
                CombinedPriceListsUpdateEvent::NAME
            );
        $this->combinedPriceListsBuilderFacade->expects($this->once())
            ->method('processAssignments')
            ->with($cpl, $assignTo);

        $this->logger->expects($this->never())
            ->method('error');

        $this->assertEquals(
            $this->processor::ACK,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );
    }

    /**
     * @dataProvider productsDataProvider
     */
    public function testProcessNotReadyCpl(array $products)
    {
        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 1]);
        $assignTo = ['config' => true];
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn([
                'cpl' => $cpl,
                'jobId' => 100,
                'products' => $products,
                'assign_to' => $assignTo
            ]);

        $rootJob = $this->createMock(Job::class);
        $rootJob->expects($this->any())
            ->method('getId')
            ->willReturn(42);
        $job = $this->createMock(Job::class);
        $job->expects($this->any())
            ->method('getRootJob')
            ->willReturn($rootJob);

        $this->jobRunner->expects($this->once())
            ->method('runDelayed')
            ->willReturnCallback(
                function ($ownerId, $closure) use ($job) {
                    return $closure($this->jobRunner, $job);
                }
            );

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($em);
        $this->assertActivityRecordsRemoval($products, $cpl);

        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects($this->once())
            ->method('commit');
        $em->expects($this->never())
            ->method('rollback');
        $this->indexationTriggerHandler->expects($this->once())
            ->method('startCollect');
        $this->indexationTriggerHandler->expects($this->once())
            ->method('commit');
        $this->indexationTriggerHandler->expects($this->never())
            ->method('rollback');
        $this->statusHandler->expects($this->once())
            ->method('isReadyForBuild')
            ->with($cpl)
            ->willReturn(false);
        $this->combinedPriceListsBuilderFacade->expects($this->never())
            ->method('rebuild');
        $this->combinedPriceListsBuilderFacade->expects($this->never())
            ->method('triggerProductIndexation');
        $this->dispatcher->expects($this->never())
            ->method('dispatch')
            ->with(
                new CombinedPriceListsUpdateEvent([$cpl->getId()]),
                CombinedPriceListsUpdateEvent::NAME
            );
        $this->combinedPriceListsBuilderFacade->expects($this->once())
            ->method('processAssignments')
            ->with($cpl, $assignTo);

        $this->logger->expects($this->never())
            ->method('error');

        $this->assertEquals(
            $this->processor::ACK,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );
    }

    public function productsDataProvider(): array
    {
        return [
            [[]],
            [[1, 2]]
        ];
    }

    protected function assertActivityRecordsRemoval(array $products, CombinedPriceList $cpl): void
    {
        $repo = $this->createMock(CombinedPriceListBuildActivityRepository::class);
        if ($products) {
            $repo->expects($this->once())
                ->method('deleteActivityRecordsForJob')
                ->with(42);
        } else {
            $repo->expects($this->once())
                ->method('deleteActivityRecordsForCombinedPriceList')
                ->with($cpl);
        }
        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->with(CombinedPriceListBuildActivity::class)
            ->willReturn($repo);
    }
}
