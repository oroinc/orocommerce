<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Manager\PriceManager;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PriceManagerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ClassMetadata|\PHPUnit\Framework\MockObject\MockObject */
    protected $classMetadata;

    /** @var UnitOfWork|\PHPUnit\Framework\MockObject\MockObject */
    protected $unitOfWork;

    /** @var ProductPriceRepository|\PHPUnit\Framework\MockObject\MockObject */
    protected $repository;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $entityManager;

    /** @var ShardManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $shardManager;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $eventDispatcher;

    /** @var PriceManager */
    protected $manager;

    protected function setUp()
    {
        $this->unitOfWork = $this->createMock(UnitOfWork::class);
        $this->repository = $this->createMock(ProductPriceRepository::class);
        $this->classMetadata = $this->createMock(ClassMetadata::class);

        $this->entityManager = $this->createMock(EntityManager::class);
        $this->entityManager->expects($this->any())->method('getUnitOfWork')->willReturn($this->unitOfWork);
        $this->entityManager->expects($this->any())->method('getRepository')->willReturn($this->repository);
        $this->entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->with(ProductPrice::class)
            ->willReturn($this->classMetadata);

        $this->shardManager = $this->createMock(ShardManager::class);
        $this->shardManager->expects($this->any())->method('getEntityManager')->willReturn($this->entityManager);

        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->manager = new PriceManager($this->shardManager, $this->eventDispatcher);
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

        $this->repository->expects($this->once())->method('remove')->with($this->shardManager, $priceToRemove);

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

        $this->manager->persist($priceToPersist);
        $this->manager->remove($priceToRemove);

        $this->manager->flush();
    }

    /**
     * @param int $id
     * @return ProductPrice
     */
    protected function getProductPrice($id = null)
    {
        return $this->getEntity(ProductPrice::class, ['id' => $id]);
    }
}
