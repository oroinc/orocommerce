<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\MessageQueueBundle\Client\MessageBufferManager;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Event\ProductPriceRemove;
use Oro\Bundle\PricingBundle\Event\ProductPriceSaveAfterEvent;
use Oro\Bundle\PricingBundle\Event\ProductPricesUpdated;
use Oro\Bundle\PricingBundle\Manager\PriceManager;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PriceManagerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var UnitOfWork|\PHPUnit\Framework\MockObject\MockObject */
    private $unitOfWork;

    /** @var ProductPriceRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var ShardManager|\PHPUnit\Framework\MockObject\MockObject */
    private $shardManager;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var MessageBufferManager|\PHPUnit\Framework\MockObject\MockObject */
    private $messageBufferManager;

    /** @var PriceManager */
    private $manager;

    protected function setUp(): void
    {
        $this->unitOfWork = $this->createMock(UnitOfWork::class);
        $this->repository = $this->createMock(ProductPriceRepository::class);

        $this->entityManager = $this->createMock(EntityManager::class);
        $this->entityManager->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($this->unitOfWork);
        $this->entityManager->expects($this->any())
            ->method('getRepository')
            ->willReturn($this->repository);
        $this->entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->with(ProductPrice::class)
            ->willReturn($this->createMock(ClassMetadata::class));

        $this->shardManager = $this->createMock(ShardManager::class);
        $this->shardManager->expects($this->any())->method('getEntityManager')->willReturn($this->entityManager);

        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->messageBufferManager = $this->createMock(MessageBufferManager::class);

        $this->manager = new PriceManager(
            $this->shardManager,
            $this->eventDispatcher,
            $this->messageBufferManager
        );
    }

    /**
     * @param int $id
     *
     * @return ProductPrice
     */
    private function getProductPrice(int $id = null): ProductPrice
    {
        return $this->getEntity(ProductPrice::class, ['id' => $id]);
    }

    public function testFlush()
    {
        $priceToPersist = $this->getProductPrice();
        $priceToRemove = $this->getProductPrice(101);

        $id = mt_rand();

        $this->repository->expects($this->once())
            ->method('save')
            ->with($this->shardManager, $priceToPersist)
            ->willReturnCallback(
                function ($shardManager, ProductPrice $price) use ($id) {
                    $price->setId($id);
                }
            );

        $this->repository->expects($this->once())
            ->method('remove')
            ->with($this->shardManager, $priceToRemove);

        $changeSet = ['id' => $id];

        $this->unitOfWork->expects($this->any())
            ->method('getEntityChangeSet')
            ->with($priceToPersist)
            ->willReturn($changeSet);

        $this->unitOfWork->expects($this->once())
            ->method('registerManaged')
            ->with($priceToPersist, ['id' => $id], $changeSet);

        $this->entityManager->expects($this->once())
            ->method('detach')
            ->with($priceToRemove);

        $this->eventDispatcher->expects($this->exactly(3))
            ->method('dispatch')
            ->withConsecutive(
                [ProductPriceRemove::NAME, $this->isInstanceOf(ProductPriceRemove::class)],
                [ProductPriceSaveAfterEvent::NAME, $this->isInstanceOf(ProductPriceSaveAfterEvent::class)],
                [ProductPricesUpdated::NAME, $this->isInstanceOf(ProductPricesUpdated::class)]
            );

        $this->messageBufferManager->expects($this->once())
            ->method('flushBuffer');

        $this->manager->persist($priceToPersist);
        $this->manager->remove($priceToRemove);

        $this->manager->flush();
    }
}
