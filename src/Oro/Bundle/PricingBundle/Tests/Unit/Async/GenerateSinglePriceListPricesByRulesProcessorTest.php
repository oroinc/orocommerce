<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Async;

use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\NotificationBundle\NotificationAlert\NotificationAlertManager;
use Oro\Bundle\PricingBundle\Async\GenerateSinglePriceListPricesByRulesProcessor;
use Oro\Bundle\PricingBundle\Async\PriceListCalculationNotificationAlert;
use Oro\Bundle\PricingBundle\Async\Topic\GenerateSinglePriceListPricesByRulesTopic;
use Oro\Bundle\PricingBundle\Builder\PriceListProductAssignmentBuilder;
use Oro\Bundle\PricingBundle\Builder\ProductPriceBuilder;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class GenerateSinglePriceListPricesByRulesProcessorTest extends TestCase
{
    use EntityTrait;

    private ManagerRegistry|MockObject $doctrine;
    private PriceListProductAssignmentBuilder|MockObject $assignmentBuilder;
    private ProductPriceBuilder|MockObject $priceBuilder;
    private NotificationAlertManager|MockObject $notificationAlertManager;
    private JobRunner|MockObject $jobRunner;
    private LoggerInterface|MockObject $logger;
    private GenerateSinglePriceListPricesByRulesProcessor $processor;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->assignmentBuilder = $this->createMock(PriceListProductAssignmentBuilder::class);
        $this->priceBuilder = $this->createMock(ProductPriceBuilder::class);
        $this->notificationAlertManager = $this->createMock(NotificationAlertManager::class);
        $this->jobRunner = $this->createMock(JobRunner::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->processor = new GenerateSinglePriceListPricesByRulesProcessor(
            $this->doctrine,
            $this->assignmentBuilder,
            $this->priceBuilder,
            $this->notificationAlertManager,
            $this->jobRunner
        );
        $this->processor->setLogger($this->logger);
    }

    public function testGetSubscribedTopics(): void
    {
        self::assertEquals(
            [GenerateSinglePriceListPricesByRulesTopic::getName()],
            GenerateSinglePriceListPricesByRulesProcessor::getSubscribedTopics()
        );
    }

    public function testProcessSuccess(): void
    {
        $priceListId = 1;
        $productIds = [10, 20, 30];
        $version = 100;
        $jobId = 200;

        $updatedAt = new \DateTime('2024-01-01 12:00:00');
        $priceList = $this->getEntity(PriceList::class, ['id' => $priceListId]);
        $priceList->setUpdatedAt($updatedAt);
        $priceList->setProductAssignmentRule('product.sku != ""');

        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::once())
            ->method('getBody')
            ->willReturn([
                'priceListId' => $priceListId,
                'products' => $productIds,
                'version' => $version,
                'jobId' => $jobId
            ]);

        $em = $this->createMock(EntityManagerInterface::class);

        $repository = $this->createMock(PriceListRepository::class);
        $repository->expects(self::once())
            ->method('find')
            ->with($priceListId)
            ->willReturn($priceList);
        $repository->expects(self::once())
            ->method('updatePriceListsActuality')
            ->with([$priceList], true);

        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(PriceList::class)
            ->willReturn($repository);

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($em);

        $em->expects(self::once())
            ->method('beginTransaction');
        $em->expects(self::once())
            ->method('commit');
        $em->expects(self::never())
            ->method('rollback');
        $em->expects(self::once())
            ->method('refresh')
            ->with($priceList);
        $em->expects(self::once())
            ->method('getRepository')
            ->with(PriceList::class)
            ->willReturn($repository);

        $this->assignmentBuilder->expects(self::once())
            ->method('buildByPriceListWithoutEventDispatch')
            ->with($priceList, $productIds);

        $this->priceBuilder->expects(self::exactly(2))
            ->method('setVersion')
            ->withConsecutive(
                [$version],
                [null]
            );
        $this->priceBuilder->expects(self::once())
            ->method('buildByPriceListWithoutTriggers')
            ->with($priceList, $productIds);

        $this->notificationAlertManager->expects(self::exactly(2))
            ->method('resolveNotificationAlertByOperationAndItemIdForCurrentUser')
            ->withConsecutive(
                [
                    PriceListCalculationNotificationAlert::OPERATION_ASSIGNED_PRODUCTS_BUILD,
                    $priceListId
                ],
                [
                    PriceListCalculationNotificationAlert::OPERATION_PRICE_RULES_BUILD,
                    $priceListId
                ]
            );

        $this->jobRunner->expects(self::once())
            ->method('runDelayed')
            ->with($jobId, self::isInstanceOf(\Closure::class))
            ->willReturnCallback(function ($jobId, $closure) {
                $job = $this->createMock(Job::class);
                return $closure($this->jobRunner, $job);
            });

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testProcessSuccessWithoutProductAssignmentRule(): void
    {
        $priceListId = 1;
        $productIds = [10, 20, 30];
        $version = 100;
        $jobId = 200;

        $updatedAt = new \DateTime('2024-01-01 12:00:00');
        $priceList = $this->getEntity(PriceList::class, ['id' => $priceListId]);
        $priceList->setUpdatedAt($updatedAt);

        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::once())
            ->method('getBody')
            ->willReturn([
                'priceListId' => $priceListId,
                'products' => $productIds,
                'version' => $version,
                'jobId' => $jobId
            ]);

        $em = $this->createMock(EntityManagerInterface::class);

        $repository = $this->createMock(PriceListRepository::class);
        $repository->expects(self::once())
            ->method('find')
            ->with($priceListId)
            ->willReturn($priceList);
        $repository->expects(self::once())
            ->method('updatePriceListsActuality')
            ->with([$priceList], true);

        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(PriceList::class)
            ->willReturn($repository);

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($em);

        $em->expects(self::once())
            ->method('beginTransaction');
        $em->expects(self::once())
            ->method('commit');
        $em->expects(self::never())
            ->method('rollback');
        $em->expects(self::once())
            ->method('refresh')
            ->with($priceList);
        $em->expects(self::once())
            ->method('getRepository')
            ->with(PriceList::class)
            ->willReturn($repository);

        // Should not be called when there's no product assignment rule
        $this->assignmentBuilder->expects(self::never())
            ->method('buildByPriceListWithoutEventDispatch');

        $this->priceBuilder->expects(self::exactly(2))
            ->method('setVersion')
            ->withConsecutive(
                [$version],
                [null]
            );
        $this->priceBuilder->expects(self::once())
            ->method('buildByPriceListWithoutTriggers')
            ->with($priceList, $productIds);

        $this->notificationAlertManager->expects(self::once())
            ->method('resolveNotificationAlertByOperationAndItemIdForCurrentUser')
            ->with(
                PriceListCalculationNotificationAlert::OPERATION_PRICE_RULES_BUILD,
                $priceListId
            );

        $this->jobRunner->expects(self::once())
            ->method('runDelayed')
            ->with($jobId, self::isInstanceOf(\Closure::class))
            ->willReturnCallback(function ($jobId, $closure) {
                $job = $this->createMock(Job::class);
                return $closure($this->jobRunner, $job);
            });

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testProcessWhenPriceListNotFound(): void
    {
        $priceListId = 1;
        $productIds = [10, 20, 30];
        $version = 100;
        $jobId = 200;

        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::once())
            ->method('getBody')
            ->willReturn([
                'priceListId' => $priceListId,
                'products' => $productIds,
                'version' => $version,
                'jobId' => $jobId
            ]);

        $repository = $this->createMock(PriceListRepository::class);
        $repository->expects(self::once())
            ->method('find')
            ->with($priceListId)
            ->willReturn(null);

        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(PriceList::class)
            ->willReturn($repository);

        $this->logger->expects(self::once())
            ->method('warning')
            ->with(
                'Price list not found.',
                ['priceListId' => $priceListId]
            );

        $this->assignmentBuilder->expects(self::never())
            ->method('buildByPriceListWithoutEventDispatch');
        $this->priceBuilder->expects(self::never())
            ->method('buildByPriceListWithoutTriggers');
        $this->priceBuilder->expects(self::exactly(2))
            ->method('setVersion')
            ->withConsecutive(
                [$version],
                [null]
            );

        $this->jobRunner->expects(self::once())
            ->method('runDelayed')
            ->with($jobId, self::isInstanceOf(\Closure::class))
            ->willReturnCallback(function ($jobId, $closure) {
                $job = $this->createMock(Job::class);
                return $closure($this->jobRunner, $job);
            });

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testProcessWithRetryableException(): void
    {
        $priceListId = 1;
        $productIds = [10, 20, 30];
        $version = 100;
        $jobId = 200;

        $updatedAt = new \DateTime('2024-01-01 12:00:00');
        $priceList = $this->getEntity(PriceList::class, ['id' => $priceListId]);
        $priceList->setUpdatedAt($updatedAt);
        $priceList->setProductAssignmentRule('product.sku != ""');

        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::once())
            ->method('getBody')
            ->willReturn([
                'priceListId' => $priceListId,
                'products' => $productIds,
                'version' => $version,
                'jobId' => $jobId
            ]);

        $em = $this->createMock(EntityManagerInterface::class);

        $repository = $this->createMock(PriceListRepository::class);
        $repository->expects(self::once())
            ->method('find')
            ->with($priceListId)
            ->willReturn($priceList);

        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(PriceList::class)
            ->willReturn($repository);

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($em);

        $em->expects(self::once())
            ->method('beginTransaction');
        $em->expects(self::once())
            ->method('rollback');
        $em->expects(self::never())
            ->method('commit');

        $exception = $this->createMock(DeadlockException::class);

        $this->assignmentBuilder->expects(self::once())
            ->method('buildByPriceListWithoutEventDispatch')
            ->with($priceList, $productIds)
            ->willThrowException($exception);
        $this->priceBuilder->expects(self::exactly(2))
            ->method('setVersion')
            ->withConsecutive(
                [$version],
                [null]
            );

        $this->logger->expects(self::once())
            ->method('warning')
            ->with('Job redelivered', ['exception' => $exception]);

        $this->jobRunner->expects(self::once())
            ->method('runDelayed')
            ->with($jobId, self::isInstanceOf(\Closure::class))
            ->willReturnCallback(function ($jobId, $closure) use ($exception) {
                $job = $this->createMock(Job::class);
                $closure($this->jobRunner, $job);
                throw $exception;
            });

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::REQUEUE, $result);
    }

    public function testProcessWithUnexpectedException(): void
    {
        $priceListId = 1;
        $productIds = [10, 20, 30];
        $version = 100;
        $jobId = 200;

        $updatedAt = new \DateTime('2024-01-01 12:00:00');
        $priceList = $this->getEntity(PriceList::class, ['id' => $priceListId]);
        $priceList->setUpdatedAt($updatedAt);
        $priceList->setProductAssignmentRule('product.sku != ""');

        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::once())
            ->method('getBody')
            ->willReturn([
                'priceListId' => $priceListId,
                'products' => $productIds,
                'version' => $version,
                'jobId' => $jobId
            ]);

        $em = $this->createMock(EntityManagerInterface::class);

        $repository = $this->createMock(PriceListRepository::class);
        $repository->expects(self::once())
            ->method('find')
            ->with($priceListId)
            ->willReturn($priceList);

        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(PriceList::class)
            ->willReturn($repository);

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($em);

        $em->expects(self::once())
            ->method('beginTransaction');
        $em->expects(self::once())
            ->method('rollback');
        $em->expects(self::never())
            ->method('commit');

        $exception = new \Exception('Unexpected error');

        $this->assignmentBuilder->expects(self::once())
            ->method('buildByPriceListWithoutEventDispatch')
            ->with($priceList, $productIds)
            ->willThrowException($exception);
        $this->priceBuilder->expects(self::exactly(2))
            ->method('setVersion')
            ->withConsecutive(
                [$version],
                [null]
            );

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Price List Assigned Products build.',
                ['exception' => $exception]
            );

        $this->notificationAlertManager->expects(self::exactly(2))
            ->method('addNotificationAlert')
            ->with(self::isInstanceOf(PriceListCalculationNotificationAlert::class));

        $this->jobRunner->expects(self::once())
            ->method('runDelayed')
            ->with($jobId, self::isInstanceOf(\Closure::class))
            ->willReturnCallback(function ($jobId, $closure) {
                $job = $this->createMock(Job::class);
                return $closure($this->jobRunner, $job);
            });

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testProcessWithNullVersion(): void
    {
        $priceListId = 1;
        $productIds = [10, 20, 30];
        $version = null;
        $jobId = 200;

        $updatedAt = new \DateTime('2024-01-01 12:00:00');
        $priceList = $this->getEntity(PriceList::class, ['id' => $priceListId]);
        $priceList->setUpdatedAt($updatedAt);

        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::once())
            ->method('getBody')
            ->willReturn([
                'priceListId' => $priceListId,
                'products' => $productIds,
                'version' => $version,
                'jobId' => $jobId
            ]);

        $em = $this->createMock(EntityManagerInterface::class);

        $repository = $this->createMock(PriceListRepository::class);
        $repository->expects(self::once())
            ->method('find')
            ->with($priceListId)
            ->willReturn($priceList);
        $repository->expects(self::once())
            ->method('updatePriceListsActuality')
            ->with([$priceList], true);

        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(PriceList::class)
            ->willReturn($repository);

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($em);

        $em->expects(self::once())
            ->method('beginTransaction');
        $em->expects(self::once())
            ->method('commit');
        $em->expects(self::once())
            ->method('refresh')
            ->with($priceList);
        $em->expects(self::once())
            ->method('getRepository')
            ->with(PriceList::class)
            ->willReturn($repository);

        $this->priceBuilder->expects(self::exactly(2))
            ->method('setVersion')
            ->withConsecutive(
                [$version],
                [null]
            );
        $this->priceBuilder->expects(self::once())
            ->method('buildByPriceListWithoutTriggers')
            ->with($priceList, $productIds);

        $this->jobRunner->expects(self::once())
            ->method('runDelayed')
            ->with($jobId, self::isInstanceOf(\Closure::class))
            ->willReturnCallback(function ($jobId, $closure) {
                $job = $this->createMock(Job::class);
                return $closure($this->jobRunner, $job);
            });

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testProcessDoesNotUpdateActualityWhenUpdatedAtChanged(): void
    {
        $priceListId = 1;
        $productIds = [10, 20, 30];
        $version = 100;
        $jobId = 200;

        $originalUpdatedAt = new \DateTime('2024-01-01 12:00:00');
        $newUpdatedAt = new \DateTime('2024-01-01 13:00:00');

        $priceList = $this->getEntity(PriceList::class, ['id' => $priceListId]);
        $priceList->setUpdatedAt($originalUpdatedAt);

        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::once())
            ->method('getBody')
            ->willReturn([
                'priceListId' => $priceListId,
                'products' => $productIds,
                'version' => $version,
                'jobId' => $jobId
            ]);

        $em = $this->createMock(EntityManagerInterface::class);

        $repository = $this->createMock(PriceListRepository::class);
        $repository->expects(self::once())
            ->method('find')
            ->with($priceListId)
            ->willReturn($priceList);

        // Should not update actuality when updatedAt has changed
        $repository->expects(self::never())
            ->method('updatePriceListsActuality');

        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(PriceList::class)
            ->willReturn($repository);

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($em);

        $em->expects(self::once())
            ->method('beginTransaction');
        $em->expects(self::once())
            ->method('commit');
        $em->expects(self::once())
            ->method('refresh')
            ->with($priceList)
            ->willReturnCallback(function ($priceList) use ($newUpdatedAt) {
                // Simulate that updatedAt has changed after refresh
                $priceList->setUpdatedAt($newUpdatedAt);
            });
        $em->expects(self::never())
            ->method('getRepository');

        $this->priceBuilder->expects(self::once())
            ->method('buildByPriceListWithoutTriggers')
            ->with($priceList, $productIds);

        $this->jobRunner->expects(self::once())
            ->method('runDelayed')
            ->with($jobId, self::isInstanceOf(\Closure::class))
            ->willReturnCallback(function ($jobId, $closure) {
                $job = $this->createMock(Job::class);
                return $closure($this->jobRunner, $job);
            });

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testProcessWithEmptyProductIds(): void
    {
        $priceListId = 1;
        $productIds = [];
        $version = 100;
        $jobId = 200;

        $updatedAt = new \DateTime('2024-01-01 12:00:00');
        $priceList = $this->getEntity(PriceList::class, ['id' => $priceListId]);
        $priceList->setUpdatedAt($updatedAt);
        $priceList->setProductAssignmentRule('product.sku != ""');

        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::once())
            ->method('getBody')
            ->willReturn([
                'priceListId' => $priceListId,
                'products' => $productIds,
                'version' => $version,
                'jobId' => $jobId
            ]);

        $em = $this->createMock(EntityManagerInterface::class);

        $repository = $this->createMock(PriceListRepository::class);
        $repository->expects(self::once())
            ->method('find')
            ->with($priceListId)
            ->willReturn($priceList);
        $repository->expects(self::once())
            ->method('updatePriceListsActuality')
            ->with([$priceList], true);

        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(PriceList::class)
            ->willReturn($repository);

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($em);

        $em->expects(self::once())
            ->method('beginTransaction');
        $em->expects(self::once())
            ->method('commit');
        $em->expects(self::once())
            ->method('refresh')
            ->with($priceList);
        $em->expects(self::once())
            ->method('getRepository')
            ->with(PriceList::class)
            ->willReturn($repository);

        $this->assignmentBuilder->expects(self::once())
            ->method('buildByPriceListWithoutEventDispatch')
            ->with($priceList, $productIds);

        $this->priceBuilder->expects(self::once())
            ->method('buildByPriceListWithoutTriggers')
            ->with($priceList, $productIds);
        $this->priceBuilder->expects(self::exactly(2))
            ->method('setVersion')
            ->withConsecutive(
                [$version],
                [null]
            );

        $this->jobRunner->expects(self::once())
            ->method('runDelayed')
            ->with($jobId, self::isInstanceOf(\Closure::class))
            ->willReturnCallback(function ($jobId, $closure) {
                $job = $this->createMock(Job::class);
                return $closure($this->jobRunner, $job);
            });

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }
}
