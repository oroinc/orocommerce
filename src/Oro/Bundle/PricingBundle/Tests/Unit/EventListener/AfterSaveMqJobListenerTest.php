<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ApiBundle\Entity\AsyncOperation;
use Oro\Bundle\PricingBundle\Async\Topic\GenerateDependentPriceListPricesTopic;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\EventListener\AfterSaveMqJobListener;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Event\AfterSaveJobEvent;
use Oro\Component\MessageQueue\Job\Job;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AfterSaveMqJobListenerTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private ShardManager&MockObject $shardManager;
    private MessageProducerInterface&MockObject $producer;
    private AfterSaveMqJobListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->shardManager = $this->createMock(ShardManager::class);
        $this->producer = $this->createMock(MessageProducerInterface::class);

        $this->listener = new AfterSaveMqJobListener(
            $this->doctrine,
            $this->shardManager,
            $this->producer
        );
    }

    public function testOnAfterSaveWhenJobIsNotSuccess(): void
    {
        $job = new Job();
        $job->setStatus(Job::STATUS_FAILED);

        $event = new AfterSaveJobEvent($job);

        $this->doctrine->expects(self::never())
            ->method('getManagerForClass');
        $this->producer->expects(self::never())
            ->method('send');

        $this->listener->onAfterSave($event);
    }

    public function testOnAfterSaveWhenJobIsNotRoot(): void
    {
        $rootJob = new Job();
        $childJob = new Job();
        $childJob->setRootJob($rootJob);
        $childJob->setStatus(Job::STATUS_SUCCESS);

        $event = new AfterSaveJobEvent($childJob);

        $this->doctrine->expects(self::never())
            ->method('getManagerForClass');
        $this->producer->expects(self::never())
            ->method('send');

        $this->listener->onAfterSave($event);
    }

    public function testOnAfterSaveWhenNoOperationIdInJobData(): void
    {
        $job = new Job();
        $job->setStatus(Job::STATUS_SUCCESS);
        $job->setData(['some_key' => 'value']);

        $event = new AfterSaveJobEvent($job);

        $this->doctrine->expects(self::never())
            ->method('getManagerForClass');
        $this->producer->expects(self::never())
            ->method('send');

        $this->listener->onAfterSave($event);
    }

    public function testOnAfterSaveWhenShardingIsEnabled(): void
    {
        $job = new Job();
        $job->setStatus(Job::STATUS_SUCCESS);
        $job->setData(['api_operation_id' => 123]);

        $event = new AfterSaveJobEvent($job);

        $this->shardManager->expects(self::once())
            ->method('isShardingEnabled')
            ->willReturn(true);

        $this->doctrine->expects(self::never())
            ->method('getManagerForClass');
        $this->producer->expects(self::never())
            ->method('send');

        $this->listener->onAfterSave($event);
    }

    public function testOnAfterSaveWhenOperationNotFound(): void
    {
        $operationId = 123;
        $job = new Job();
        $job->setStatus(Job::STATUS_SUCCESS);
        $job->setData(['api_operation_id' => $operationId]);

        $event = new AfterSaveJobEvent($job);

        $this->shardManager->expects(self::once())
            ->method('isShardingEnabled')
            ->willReturn(false);

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(AsyncOperation::class)
            ->willReturn($em);

        $em->expects(self::once())
            ->method('find')
            ->with(AsyncOperation::class, $operationId)
            ->willReturn(null);

        $this->doctrine->expects(self::never())
            ->method('getRepository');
        $this->producer->expects(self::never())
            ->method('send');

        $this->listener->onAfterSave($event);
    }

    public function testOnAfterSaveWhenOperationIsNotForProductPrice(): void
    {
        $operationId = 123;
        $job = new Job();
        $job->setStatus(Job::STATUS_SUCCESS);
        $job->setData(['api_operation_id' => $operationId]);

        $event = new AfterSaveJobEvent($job);

        $this->shardManager->expects(self::once())
            ->method('isShardingEnabled')
            ->willReturn(false);

        $operation = new AsyncOperation();
        $operation->setEntityClass('SomeOtherEntity');

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(AsyncOperation::class)
            ->willReturn($em);

        $em->expects(self::once())
            ->method('find')
            ->with(AsyncOperation::class, $operationId)
            ->willReturn($operation);

        $this->doctrine->expects(self::never())
            ->method('getRepository');
        $this->producer->expects(self::never())
            ->method('send');

        $this->listener->onAfterSave($event);
    }

    public function testOnAfterSaveWhenNoPriceListsAffected(): void
    {
        $operationId = 123;
        $job = new Job();
        $job->setStatus(Job::STATUS_SUCCESS);
        $job->setData(['api_operation_id' => $operationId]);

        $event = new AfterSaveJobEvent($job);

        $this->shardManager->expects(self::once())
            ->method('isShardingEnabled')
            ->willReturn(false);

        $operation = new AsyncOperation();
        $operation->setEntityClass(ProductPrice::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(AsyncOperation::class)
            ->willReturn($em);

        $em->expects(self::once())
            ->method('find')
            ->with(AsyncOperation::class, $operationId)
            ->willReturn($operation);

        $repository = $this->createMock(ProductPriceRepository::class);
        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(ProductPrice::class)
            ->willReturn($repository);

        $repository->expects(self::once())
            ->method('getPriceListIdsAffectedByVersion')
            ->with($operationId)
            ->willReturn([]);

        $this->producer->expects(self::never())
            ->method('send');

        $this->listener->onAfterSave($event);
    }

    public function testOnAfterSaveSuccessWithSinglePriceList(): void
    {
        $operationId = 123;
        $priceListId = 456;

        $job = new Job();
        $job->setStatus(Job::STATUS_SUCCESS);
        $job->setData(['api_operation_id' => $operationId]);

        $event = new AfterSaveJobEvent($job);

        $this->shardManager->expects(self::once())
            ->method('isShardingEnabled')
            ->willReturn(false);

        $operation = new AsyncOperation();
        $operation->setEntityClass(ProductPrice::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(AsyncOperation::class)
            ->willReturn($em);

        $em->expects(self::once())
            ->method('find')
            ->with(AsyncOperation::class, $operationId)
            ->willReturn($operation);

        $repository = $this->createMock(ProductPriceRepository::class);
        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(ProductPrice::class)
            ->willReturn($repository);

        $repository->expects(self::once())
            ->method('getPriceListIdsAffectedByVersion')
            ->with($operationId)
            ->willReturn([$priceListId]);

        $this->producer->expects(self::once())
            ->method('send')
            ->with(
                GenerateDependentPriceListPricesTopic::getName(),
                [
                    'sourcePriceListId' => $priceListId,
                    'version' => $operationId
                ]
            );

        $this->listener->onAfterSave($event);
    }

    public function testOnAfterSaveSuccessWithMultiplePriceLists(): void
    {
        $operationId = 123;
        $priceListIds = [456, 789, 101112];

        $job = new Job();
        $job->setStatus(Job::STATUS_SUCCESS);
        $job->setData(['api_operation_id' => $operationId]);

        $event = new AfterSaveJobEvent($job);

        $this->shardManager->expects(self::once())
            ->method('isShardingEnabled')
            ->willReturn(false);

        $operation = new AsyncOperation();
        $operation->setEntityClass(ProductPrice::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(AsyncOperation::class)
            ->willReturn($em);

        $em->expects(self::once())
            ->method('find')
            ->with(AsyncOperation::class, $operationId)
            ->willReturn($operation);

        $repository = $this->createMock(ProductPriceRepository::class);
        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(ProductPrice::class)
            ->willReturn($repository);

        $repository->expects(self::once())
            ->method('getPriceListIdsAffectedByVersion')
            ->with($operationId)
            ->willReturn($priceListIds);

        $this->producer->expects(self::exactly(3))
            ->method('send')
            ->withConsecutive(
                [
                    GenerateDependentPriceListPricesTopic::getName(),
                    [
                        'sourcePriceListId' => $priceListIds[0],
                        'version' => $operationId
                    ]
                ],
                [
                    GenerateDependentPriceListPricesTopic::getName(),
                    [
                        'sourcePriceListId' => $priceListIds[1],
                        'version' => $operationId
                    ]
                ],
                [
                    GenerateDependentPriceListPricesTopic::getName(),
                    [
                        'sourcePriceListId' => $priceListIds[2],
                        'version' => $operationId
                    ]
                ]
            );

        $this->listener->onAfterSave($event);
    }
}
