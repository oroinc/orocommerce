<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity\EntityListener;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\PricingBundle\Entity\EntityListener\ProductPricesListener;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Event\ProductPricesUpdated;
use Oro\Bundle\PricingBundle\Handler\CombinedPriceListBuildTriggerHandler;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerHandler;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductPricesListenerTest extends TestCase
{
    use EntityTrait;

    private ProductPricesListener $productPricesListener;
    private CombinedPriceListBuildTriggerHandler|MockObject $combinedPriceListBuildTriggerHandler;
    private PriceListTriggerHandler|MockObject $priceListTriggerHandler;

    #[\Override]
    protected function setUp(): void
    {
        $this->combinedPriceListBuildTriggerHandler = $this->createMock(CombinedPriceListBuildTriggerHandler::class);
        $this->priceListTriggerHandler = $this->createMock(PriceListTriggerHandler::class);

        $this->productPricesListener = new ProductPricesListener(
            $this->combinedPriceListBuildTriggerHandler,
            $this->priceListTriggerHandler
        );
    }

    public function testIsDisabled(): void
    {
        $this->productPricesListener->setEnabled(false);

        $this->combinedPriceListBuildTriggerHandler
            ->expects($this->never())
            ->method('handle');

        $entityManager = $this->createMock(EntityManager::class);
        $event = new ProductPricesUpdated($entityManager, [], [], [], []);
        $this->productPricesListener->onPricesUpdated($event);
    }

    public function testOnPricesUpdated(): void
    {
        $priceListToSaveUpdate = $this->getEntity(PriceList::class, ['id' => 1]);
        $priceListToToRemove = $this->getEntity(PriceList::class, ['id' => 2]);

        $productPriceToSave = $this->getEntity(ProductPrice::class, ['priceList' => $priceListToSaveUpdate]);
        $productPriceToUpdate = $this->getEntity(ProductPrice::class, ['priceList' => $priceListToSaveUpdate]);
        $productPriceToRemove = $this->getEntity(ProductPrice::class, ['priceList' => $priceListToToRemove]);

        $this->combinedPriceListBuildTriggerHandler
            ->expects($this->exactly(2))
            ->method('handle')
            ->withConsecutive(
                [$priceListToSaveUpdate],
                [$priceListToToRemove]
            );

        $entityManager = $this->createMock(EntityManager::class);
        $event = new ProductPricesUpdated(
            $entityManager,
            [$productPriceToRemove],
            [$productPriceToSave],
            [$productPriceToUpdate],
            []
        );
        $this->productPricesListener->onPricesUpdated($event);
    }

    public function testOnPricesUpdatedFiltersSavedPricesWithVersion(): void
    {
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);

        $productPriceWithoutVersion = $this->getEntity(ProductPrice::class, ['priceList' => $priceList]);
        $productPriceWithVersion = $this->getEntity(ProductPrice::class, ['priceList' => $priceList, 'version' => 123]);

        // Only one price list should be handled (from price without version)
        $this->combinedPriceListBuildTriggerHandler
            ->expects($this->once())
            ->method('handle')
            ->with($priceList);

        $entityManager = $this->createMock(EntityManager::class);
        $event = new ProductPricesUpdated(
            $entityManager,
            [],
            [$productPriceWithoutVersion, $productPriceWithVersion],
            [],
            []
        );
        $this->productPricesListener->onPricesUpdated($event);
    }

    public function testOnPricesUpdatedFiltersUpdatedPricesWithVersionInChangeset(): void
    {
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);

        $productPriceWithoutVersionChange = $this->getEntity(
            ProductPrice::class,
            ['id' => 1, 'priceList' => $priceList]
        );
        $productPriceWithVersionChange = $this->getEntity(ProductPrice::class, ['id' => 2, 'priceList' => $priceList]);
        $productPriceWithVersionChange->setVersion(123);

        // Only one price list should be handled (from price without version change)
        $this->combinedPriceListBuildTriggerHandler
            ->expects($this->once())
            ->method('handle')
            ->with($priceList);

        $entityManager = $this->createMock(EntityManager::class);
        $changeSets = [
            1 => ['price' => [10, 20]], // No version in changeset
            2 => ['version' => [null, 123]] // Version present in changeset
        ];
        $event = new ProductPricesUpdated(
            $entityManager,
            [],
            [],
            [$productPriceWithoutVersionChange, $productPriceWithVersionChange],
            $changeSets
        );
        $this->productPricesListener->onPricesUpdated($event);
    }

    public function testOnPricesUpdatedRemovesPricesAreNotFiltered(): void
    {
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);

        $productPriceToRemove = $this->getEntity(ProductPrice::class, ['priceList' => $priceList, 'version' => 123]);

        // Removed prices are always processed regardless of version
        $this->combinedPriceListBuildTriggerHandler
            ->expects($this->once())
            ->method('handle')
            ->with($priceList);

        $entityManager = $this->createMock(EntityManager::class);
        $event = new ProductPricesUpdated(
            $entityManager,
            [$productPriceToRemove],
            [],
            [],
            []
        );
        $this->productPricesListener->onPricesUpdated($event);
    }

    public function testOnPricesUpdatedFiltersAllSavedAndUpdatedPricesWithVersion(): void
    {
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);

        $productPriceToSave = $this->getEntity(ProductPrice::class, ['priceList' => $priceList, 'version' => 123]);
        $productPriceToUpdate = $this->getEntity(ProductPrice::class, ['id' => 1, 'priceList' => $priceList]);

        // No handle calls expected when all saved/updated prices are filtered out
        $this->combinedPriceListBuildTriggerHandler
            ->expects($this->never())
            ->method('handle');

        $entityManager = $this->createMock(EntityManager::class);
        $changeSets = [
            1 => ['version' => [null, 123]] // Version present in changeset
        ];
        $event = new ProductPricesUpdated(
            $entityManager,
            [],
            [$productPriceToSave],
            [$productPriceToUpdate],
            $changeSets
        );
        $this->productPricesListener->onPricesUpdated($event);
    }
}
