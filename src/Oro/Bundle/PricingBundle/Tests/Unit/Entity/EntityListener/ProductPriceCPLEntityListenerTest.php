<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity\EntityListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CommerceEntityBundle\Storage\ExtraActionEntityStorageInterface;
use Oro\Bundle\PricingBundle\Entity\EntityListener\ProductPriceCPLEntityListener;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToProduct;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToProductRepository;
use Oro\Bundle\PricingBundle\Event\PriceListToProductSaveAfterEvent;
use Oro\Bundle\PricingBundle\Event\ProductPriceSaveAfterEvent;
use Oro\Bundle\PricingBundle\Event\ProductPricesUpdatedAfter;
use Oro\Bundle\PricingBundle\Handler\CombinedPriceListBuildTriggerHandler;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerHandler;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ProductPriceCPLEntityListenerTest extends TestCase
{
    use EntityTrait;

    private ExtraActionEntityStorageInterface|MockObject $extraActionsStorage;
    private ManagerRegistry|MockObject $registry;
    private PriceListTriggerHandler|MockObject $priceListTriggerHandler;
    private ShardManager|MockObject $shardManager;
    private EventDispatcherInterface|MockObject $eventDispatcher;
    private CombinedPriceListBuildTriggerHandler|MockObject $combinedPriceListBuildTriggerHandler;
    private ProductPriceCPLEntityListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->extraActionsStorage = $this->createMock(ExtraActionEntityStorageInterface::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->priceListTriggerHandler = $this->createMock(PriceListTriggerHandler::class);
        $this->shardManager = $this->createMock(ShardManager::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->combinedPriceListBuildTriggerHandler = $this->createMock(CombinedPriceListBuildTriggerHandler::class);

        $this->listener = new ProductPriceCPLEntityListener(
            $this->extraActionsStorage,
            $this->registry,
            $this->priceListTriggerHandler,
            $this->shardManager,
            $this->eventDispatcher,
            $this->combinedPriceListBuildTriggerHandler
        );
    }

    public function testOnSaveWithVersion(): void
    {
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $product = $this->getEntity(Product::class, ['id' => 1]);
        $productPrice = $this->getEntity(ProductPrice::class);
        $productPrice->setPriceList($priceList);
        $productPrice->setProduct($product);
        $productPrice->setVersion(123);

        $em = $this->createMock(EntityManager::class);
        $changeSet = [];
        $eventArgs = new PreUpdateEventArgs($productPrice, $em, $changeSet);
        $event = new ProductPriceSaveAfterEvent($eventArgs);

        // Mock the repository for addPriceListToProductRelation
        $priceListToProductRepository = $this->createMock(PriceListToProductRepository::class);
        $priceListToProductRepository->expects(self::once())
            ->method('createRelation')
            ->with($priceList, $product, true)
            ->willReturn(false);

        $em->expects(self::any())
            ->method('getRepository')
            ->with(PriceListToProduct::class)
            ->willReturn($priceListToProductRepository);

        $this->registry->expects(self::any())
            ->method('getManagerForClass')
            ->with(PriceListToProduct::class)
            ->willReturn($em);

        // But should NOT handle price creation when version is present
        $this->combinedPriceListBuildTriggerHandler->expects(self::never())
            ->method('handlePriceCreation');

        $this->listener->onSave($event);
    }

    public function testOnSaveWithoutVersion(): void
    {
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $product = $this->getEntity(Product::class, ['id' => 1]);
        $productPrice = $this->getEntity(ProductPrice::class);
        $productPrice->setPriceList($priceList);
        $productPrice->setProduct($product);

        $em = $this->createMock(EntityManager::class);
        $changeSet = [];
        $eventArgs = new PreUpdateEventArgs($productPrice, $em, $changeSet);
        $event = new ProductPriceSaveAfterEvent($eventArgs);

        // Mock the repository for addPriceListToProductRelation
        $relation = $this->getEntity(PriceListToProduct::class);
        $relation->setPriceList($priceList);
        $relation->setProduct($product);
        $priceListToProductRepository = $this->createMock(PriceListToProductRepository::class);
        $priceListToProductRepository->expects(self::once())
            ->method('createRelation')
            ->with($priceList, $product, true)
            ->willReturn(true);
        $priceListToProductRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['product' => $product, 'priceList' => $priceList])
            ->willReturn($relation);

        $em->expects(self::any())
            ->method('getRepository')
            ->with(PriceListToProduct::class)
            ->willReturn($priceListToProductRepository);

        $this->registry->expects(self::any())
            ->method('getManagerForClass')
            ->with(PriceListToProduct::class)
            ->willReturn($em);

        // Should dispatch event for relation creation
        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                self::isInstanceOf(PriceListToProductSaveAfterEvent::class),
                PriceListToProductSaveAfterEvent::NAME
            );

        // Should handle price creation when no version
        $this->combinedPriceListBuildTriggerHandler->expects(self::once())
            ->method('handlePriceCreation')
            ->with($productPrice);

        $this->listener->onSave($event);
    }

    public function testOnUpdateAfterWhenDisabled(): void
    {
        $this->listener->setEnabled(false);

        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $product = $this->getEntity(Product::class, ['id' => 1]);

        // Create prices that would normally trigger mass processing
        $price1 = $this->getEntity(ProductPrice::class);
        $price1->setPriceList($priceList);
        $price1->setProduct($product);

        $price2 = $this->getEntity(ProductPrice::class);
        $price2->setPriceList($priceList);
        $price2->setProduct($product);

        $em = $this->createMock(EntityManager::class);
        $event = new ProductPricesUpdatedAfter(
            $em,
            [],
            [$price1, $price2], // saved - would trigger processing if enabled
            [],
            []
        );

        // Should not call handleMassPriceCreation because listener is disabled
        $this->combinedPriceListBuildTriggerHandler->expects(self::never())
            ->method('handleMassPriceCreation');

        $this->listener->onUpdateAfter($event);
    }

    public function testOnUpdateAfterFiltersSavedPricesWithVersion(): void
    {
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $product = $this->getEntity(Product::class, ['id' => 1]);

        $priceWithoutVersion = $this->getEntity(ProductPrice::class);
        $priceWithoutVersion->setPriceList($priceList);
        $priceWithoutVersion->setProduct($product);

        $priceWithVersion = $this->getEntity(ProductPrice::class);
        $priceWithVersion->setPriceList($priceList);
        $priceWithVersion->setProduct($product);
        $priceWithVersion->setVersion(123);

        $em = $this->createMock(EntityManager::class);
        $event = new ProductPricesUpdatedAfter(
            $em,
            [],
            [$priceWithoutVersion, $priceWithVersion], // saved
            [],
            []
        );

        // Should not call handleMassPriceCreation because only 1 price without version (< 2)
        $this->combinedPriceListBuildTriggerHandler->expects(self::never())
            ->method('handleMassPriceCreation');

        $this->listener->onUpdateAfter($event);
    }

    public function testOnUpdateAfterFiltersUpdatedPricesWithVersionInChangeset(): void
    {
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $product = $this->getEntity(Product::class, ['id' => 1]);

        $price1 = $this->getEntity(ProductPrice::class, ['id' => 1]);
        $price1->setPriceList($priceList);
        $price1->setProduct($product);

        $price2 = $this->getEntity(ProductPrice::class, ['id' => 2]);
        $price2->setPriceList($priceList);
        $price2->setProduct($product);
        $price2->setVersion(123); // Set to match changeset value

        $em = $this->createMock(EntityManager::class);
        $changeSets = [
            1 => ['price' => [10, 20]], // No version in changeset
            2 => ['version' => [null, 123]] // Version present in changeset
        ];
        $event = new ProductPricesUpdatedAfter(
            $em,
            [],
            [],
            [$price1, $price2], // updated
            $changeSets
        );

        // Should not call handleMassPriceCreation because only 1 price without version change (< 2)
        $this->combinedPriceListBuildTriggerHandler->expects(self::never())
            ->method('handleMassPriceCreation');

        $this->listener->onUpdateAfter($event);
    }

    public function testOnUpdateAfterWithMultiplePricesWithoutVersion(): void
    {
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $product = $this->getEntity(Product::class, ['id' => 1]);

        $price1 = $this->getEntity(ProductPrice::class);
        $price1->setPriceList($priceList);
        $price1->setProduct($product);

        $price2 = $this->getEntity(ProductPrice::class);
        $price2->setPriceList($priceList);
        $price2->setProduct($product);

        $em = $this->createMock(EntityManager::class);
        $event = new ProductPricesUpdatedAfter(
            $em,
            [],
            [$price1, $price2], // saved
            [],
            []
        );

        // Should call handleMassPriceCreation because 2 prices without version (>= 2)
        $this->combinedPriceListBuildTriggerHandler->expects(self::once())
            ->method('handleMassPriceCreation')
            ->with([$price1, $price2]);

        $this->listener->onUpdateAfter($event);
    }

    public function testOnUpdateAfterMixedSavedAndUpdatedPrices(): void
    {
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $product = $this->getEntity(Product::class, ['id' => 1]);

        $savedPrice = $this->getEntity(ProductPrice::class);
        $savedPrice->setPriceList($priceList);
        $savedPrice->setProduct($product);

        // New price - has id changeset indicating it's a newly created entity
        $newPrice = $this->getEntity(ProductPrice::class, ['id' => 1]);
        $newPrice->setPriceList($priceList);
        $newPrice->setProduct($product);

        $em = $this->createMock(EntityManager::class);
        $changeSets = [
            1 => ['id' => [null, 1]] // New entity - id changed from null to 1
        ];
        $event = new ProductPricesUpdatedAfter(
            $em,
            [],
            [$savedPrice], // saved
            [$newPrice], // updated (but actually new)
            $changeSets
        );

        // Should call handleMassPriceCreation because 2 new prices without version (>= 2)
        $this->combinedPriceListBuildTriggerHandler->expects(self::once())
            ->method('handleMassPriceCreation')
            ->with([$savedPrice, $newPrice]);

        $this->listener->onUpdateAfter($event);
    }

    public function testOnUpdateAfterFiltersNewPricesWithIdChangeset(): void
    {
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $product = $this->getEntity(Product::class, ['id' => 1]);

        // New price - has id changeset with null as old value
        $newPrice = $this->getEntity(ProductPrice::class, ['id' => 1]);
        $newPrice->setPriceList($priceList);
        $newPrice->setProduct($product);

        // Existing price - has other changeset
        $existingPrice = $this->getEntity(ProductPrice::class, ['id' => 2]);
        $existingPrice->setPriceList($priceList);
        $existingPrice->setProduct($product);

        $em = $this->createMock(EntityManager::class);
        $changeSets = [
            1 => ['id' => [null, 1]], // New entity - should be included
            2 => ['price' => [10, 20]] // Existing entity update - should be excluded
        ];
        $event = new ProductPricesUpdatedAfter(
            $em,
            [],
            [],
            [$newPrice, $existingPrice], // updated
            $changeSets
        );

        // Should not call handleMassPriceCreation because only 1 new price after filtering (< 2)
        $this->combinedPriceListBuildTriggerHandler->expects(self::never())
            ->method('handleMassPriceCreation');

        $this->listener->onUpdateAfter($event);
    }

    public function testOnUpdateAfterFiltersAllPricesWithVersion(): void
    {
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $product = $this->getEntity(Product::class, ['id' => 1]);

        $savedPrice = $this->getEntity(ProductPrice::class);
        $savedPrice->setPriceList($priceList);
        $savedPrice->setProduct($product);
        $savedPrice->setVersion(123);

        $updatedPrice = $this->getEntity(ProductPrice::class, ['id' => 1]);
        $updatedPrice->setPriceList($priceList);
        $updatedPrice->setProduct($product);
        $updatedPrice->setVersion(123); // Set to match changeset value

        $em = $this->createMock(EntityManager::class);
        $changeSets = [
            1 => ['version' => [null, 123]] // Version in changeset
        ];
        $event = new ProductPricesUpdatedAfter(
            $em,
            [],
            [$savedPrice], // saved with version
            [$updatedPrice], // updated with version in changeset
            $changeSets
        );

        // Should not call handleMassPriceCreation because all prices are filtered out
        $this->combinedPriceListBuildTriggerHandler->expects(self::never())
            ->method('handleMassPriceCreation');

        $this->listener->onUpdateAfter($event);
    }
}
