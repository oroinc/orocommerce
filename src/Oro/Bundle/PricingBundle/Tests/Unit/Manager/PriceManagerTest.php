<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\MessageQueueBundle\Client\MessageBufferManager;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Event\ProductPriceRemove;
use Oro\Bundle\PricingBundle\Event\ProductPriceSaveAfterEvent;
use Oro\Bundle\PricingBundle\Event\ProductPricesUpdated;
use Oro\Bundle\PricingBundle\Event\ProductPricesUpdatedAfter;
use Oro\Bundle\PricingBundle\Manager\PriceManager;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PriceManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ShardManager|\PHPUnit\Framework\MockObject\MockObject */
    private $shardManager;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var MessageBufferManager|\PHPUnit\Framework\MockObject\MockObject */
    private $messageBufferManager;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var UnitOfWork|\PHPUnit\Framework\MockObject\MockObject */
    private $unitOfWork;

    /** @var ProductPriceRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var ClassMetadata|\PHPUnit\Framework\MockObject\MockObject */
    private $classMetadata;

    /** @var PriceManager */
    private $manager;

    protected function setUp(): void
    {
        $this->shardManager = $this->createMock(ShardManager::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->messageBufferManager = $this->createMock(MessageBufferManager::class);

        $this->entityManager = $this->createMock(EntityManager::class);
        $this->unitOfWork = $this->createMock(UnitOfWork::class);
        $this->repository = $this->createMock(ProductPriceRepository::class);
        $this->classMetadata = $this->createMock(ClassMetadata::class);

        $this->entityManager->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($this->unitOfWork);
        $this->entityManager->expects($this->any())
            ->method('getRepository')
            ->willReturn($this->repository);
        $this->entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->with(ProductPrice::class)
            ->willReturn($this->classMetadata);

        $this->shardManager->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($this->entityManager);

        $this->manager = new PriceManager(
            $this->shardManager,
            $this->eventDispatcher,
            $this->messageBufferManager
        );
    }

    private function getProductPrice(int $id = null): ProductPrice
    {
        $price = new ProductPrice();
        $price->setId($id);

        return $price;
    }

    private function getPriceList(int $id = null): PriceList
    {
        $priceList = new PriceList();
        ReflectionUtil::setId($priceList, $id);

        return $priceList;
    }

    public function testFlushForNewPrice()
    {
        $price = $this->getProductPrice();
        $priceId = '123';

        $originalEntityData = ['value' => '1.0000'];
        $changeSet = ['value' => [null, '1.0000']];

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($this->identicalTo($this->shardManager), $this->identicalTo($price))
            ->willReturnCallback(function ($shardManager, ProductPrice $price) use ($priceId) {
                $price->setId($priceId);
            });
        $this->unitOfWork->expects($this->once())
            ->method('registerManaged')
            ->with($this->identicalTo($price), ['id' => $priceId], $changeSet);

        $this->repository->expects($this->never())
            ->method('remove');
        $this->entityManager->expects($this->never())
            ->method('detach');

        $this->unitOfWork->expects($this->once())
            ->method('getOriginalEntityData')
            ->with($this->identicalTo($price))
            ->willReturn($originalEntityData);
        $this->unitOfWork->expects($this->never())
            ->method('setOriginalEntityData');
        $this->unitOfWork->expects($this->once())
            ->method('computeChangeSet')
            ->with($this->identicalTo($this->classMetadata), $this->identicalTo($price));
        $this->unitOfWork->expects($this->exactly(2))
            ->method('getEntityChangeSet')
            ->with($this->identicalTo($price))
            ->willReturn($changeSet);

        $this->eventDispatcher->expects($this->exactly(3))
            ->method('dispatch')
            ->withConsecutive(
                [$this->isInstanceOf(ProductPriceSaveAfterEvent::class), ProductPriceSaveAfterEvent::NAME],
                [$this->isInstanceOf(ProductPricesUpdated::class), ProductPricesUpdated::NAME],
                [$this->isInstanceOf(ProductPricesUpdatedAfter::class), ProductPricesUpdatedAfter::NAME]
            );

        $this->messageBufferManager->expects($this->once())
            ->method('flushBuffer');

        $this->manager->persist($price);
        $this->manager->flush();
    }

    public function testFlushForUpdatedPrice()
    {
        $price = $this->getProductPrice('123');
        $price->setPrice(Price::create('2.0000', 'USD'));

        $oldPriceValue = '1.0000';
        $originalEntityData = ['value' => $oldPriceValue];
        $changeSet = ['value' => [$oldPriceValue, $price->getPrice()->getValue()]];

        $this->repository->expects($this->once())
            ->method('save')
            ->with($this->identicalTo($this->shardManager), $this->identicalTo($price));
        $this->unitOfWork->expects($this->once())
            ->method('registerManaged')
            ->with($this->identicalTo($price), ['id' => $price->getId()], $changeSet);

        $this->repository->expects($this->never())
            ->method('remove');
        $this->entityManager->expects($this->never())
            ->method('detach');

        $this->unitOfWork->expects($this->exactly(2))
            ->method('getOriginalEntityData')
            ->with($this->identicalTo($price))
            ->willReturn($originalEntityData);
        $this->unitOfWork->expects($this->never())
            ->method('setOriginalEntityData');
        $this->unitOfWork->expects($this->exactly(2))
            ->method('computeChangeSet')
            ->with($this->identicalTo($this->classMetadata), $this->identicalTo($price));
        $this->unitOfWork->expects($this->exactly(2))
            ->method('getEntityChangeSet')
            ->with($this->identicalTo($price))
            ->willReturn($changeSet);

        $this->eventDispatcher->expects($this->exactly(3))
            ->method('dispatch')
            ->withConsecutive(
                [$this->isInstanceOf(ProductPriceSaveAfterEvent::class), ProductPriceSaveAfterEvent::NAME],
                [$this->isInstanceOf(ProductPricesUpdated::class), ProductPricesUpdated::NAME],
                [$this->isInstanceOf(ProductPricesUpdatedAfter::class), ProductPricesUpdatedAfter::NAME]
            );

        $this->messageBufferManager->expects($this->once())
            ->method('flushBuffer');

        $this->manager->persist($price);
        $this->manager->flush();
    }

    public function testFlushForUpdatedPriceWhenNewPriceValueIsFloat()
    {
        $price = $this->getProductPrice('123');
        $price->setPrice(Price::create(2.0, 'USD'));

        $originalEntityData = ['value' => '1.0000'];
        $updatedOriginalEntityData = ['value' => 1.0];
        $changeSet = ['value' => ['1.0000', '2.0000']];
        $updatedChangeSet = ['value' => [1.0, 2.0]];

        $this->repository->expects($this->once())
            ->method('save')
            ->with($this->identicalTo($this->shardManager), $this->identicalTo($price));
        $this->unitOfWork->expects($this->once())
            ->method('registerManaged')
            ->with($this->identicalTo($price), ['id' => $price->getId()], $this->identicalTo($changeSet));

        $this->repository->expects($this->never())
            ->method('remove');
        $this->entityManager->expects($this->never())
            ->method('detach');

        $this->unitOfWork->expects($this->exactly(2))
            ->method('getOriginalEntityData')
            ->with($this->identicalTo($price))
            ->willReturnOnConsecutiveCalls($originalEntityData, $updatedOriginalEntityData);
        $this->unitOfWork->expects($this->once())
            ->method('setOriginalEntityData')
            ->with($this->identicalTo($price), $this->identicalTo($updatedOriginalEntityData));
        $this->unitOfWork->expects($this->exactly(2))
            ->method('computeChangeSet')
            ->with($this->identicalTo($this->classMetadata), $this->identicalTo($price));
        $this->unitOfWork->expects($this->exactly(2))
            ->method('getEntityChangeSet')
            ->with($this->identicalTo($price))
            ->willReturnOnConsecutiveCalls($changeSet, $updatedChangeSet);

        $this->eventDispatcher->expects($this->exactly(3))
            ->method('dispatch')
            ->withConsecutive(
                [$this->isInstanceOf(ProductPriceSaveAfterEvent::class), ProductPriceSaveAfterEvent::NAME],
                [$this->isInstanceOf(ProductPricesUpdated::class), ProductPricesUpdated::NAME],
                [$this->isInstanceOf(ProductPricesUpdatedAfter::class), ProductPricesUpdatedAfter::NAME]
            );

        $this->messageBufferManager->expects($this->once())
            ->method('flushBuffer');

        $this->manager->persist($price);
        $this->manager->flush();
    }

    public function testFlushForUpdatedPriceWhenPriceListChanged()
    {
        $oldPriceList = $this->getPriceList(1);
        $newPriceList = $this->getPriceList(2);

        $changedPrice = $this->getProductPrice(1);
        $changedPrice->setPriceList($newPriceList);

        $removedPrice = $this->getProductPrice(1);
        $removedPrice->setPriceList($oldPriceList);

        $savedPrice = $this->getProductPrice(2);
        $savedPrice->setPriceList($newPriceList);

        $changeSet = ['priceList' => [$oldPriceList, $newPriceList]];

        $this->unitOfWork
            ->expects($this->any())
            ->method('getEntityChangeSet')
            ->willReturnOnConsecutiveCalls($changeSet, [], []);

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->willReturnCallback(fn ($shardManager, ProductPrice $price) => $price->setId(2));

        // Remove price event
        $productPriceRemoveEvent = new ProductPriceRemove($removedPrice);
        $productPriceRemoveEvent->setEntityManager($this->entityManager);

        // Save price event
        $emptyChangeSet = [];
        $productPriceSaveAfterEventArgs = new PreUpdateEventArgs($savedPrice, $this->entityManager, $emptyChangeSet);
        $productPriceSaveAfterEvent = new ProductPriceSaveAfterEvent($productPriceSaveAfterEventArgs);

        // Update prices event
        $productPricesUpdatedEvent = new ProductPricesUpdated(
            $this->entityManager,
            [$removedPrice],
            [$savedPrice],
            [],
            []
        );

        // Update prices after event
        $productPricesUpdatedAfterEvent = new ProductPricesUpdatedAfter(
            $this->entityManager,
            [$removedPrice],
            [$savedPrice],
            [],
            []
        );

        $this->eventDispatcher
            ->expects($this->exactly(4))
            ->method('dispatch')
            ->withConsecutive(
                [$productPriceRemoveEvent, ProductPriceRemove::NAME],
                [$productPriceSaveAfterEvent, ProductPriceSaveAfterEvent::NAME],
                [$productPricesUpdatedEvent, ProductPricesUpdated::NAME],
                [$productPricesUpdatedAfterEvent, ProductPricesUpdatedAfter::NAME]
            );

        $this->messageBufferManager->expects($this->once())
            ->method('flushBuffer');

        $this->manager->persist($changedPrice);
        $this->manager->flush();
    }

    public function testFlushForUpdatedPriceWhenNoChanges()
    {
        $price = $this->getProductPrice('123');
        $price->setPrice(Price::create('1.0000', 'USD'));
        $this->repository
            ->expects($this->never())
            ->method('save');

        $this->repository
            ->expects($this->never())
            ->method('remove');

        $this->unitOfWork
            ->expects($this->once())
            ->method('getEntityChangeSet')
            ->willReturn([]);

        $this->eventDispatcher
            ->expects($this->never())
            ->method('dispatch');
        $this->messageBufferManager
            ->expects($this->never())
            ->method('flushBuffer');

        $this->manager->persist($price);
        $this->manager->flush();
    }

    public function testFlushForRemovedPrice()
    {
        $price = $this->getProductPrice('123');

        $this->repository->expects($this->never())
            ->method('save');
        $this->unitOfWork->expects($this->never())
            ->method('getEntityChangeSet');
        $this->unitOfWork->expects($this->never())
            ->method('registerManaged');

        $this->repository->expects($this->once())
            ->method('remove')
            ->with($this->identicalTo($this->shardManager), $this->identicalTo($price));
        $this->entityManager->expects($this->once())
            ->method('detach')
            ->with($this->identicalTo($price));

        $this->eventDispatcher->expects($this->exactly(3))
            ->method('dispatch')
            ->withConsecutive(
                [$this->isInstanceOf(ProductPriceRemove::class), ProductPriceRemove::NAME],
                [$this->isInstanceOf(ProductPricesUpdated::class), ProductPricesUpdated::NAME],
                [$this->isInstanceOf(ProductPricesUpdatedAfter::class), ProductPricesUpdatedAfter::NAME]
            );

        $this->messageBufferManager->expects($this->once())
            ->method('flushBuffer');

        $this->manager->remove($price);
        $this->manager->flush();
    }
}
