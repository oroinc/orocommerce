<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToPriceListRepository;
use Oro\Bundle\PricingBundle\Event\ProductPricesRemoveAfter;
use Oro\Bundle\PricingBundle\EventListener\ProductPriceRemoveListener;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductPriceRemoveListenerTest extends TestCase
{
    use EntityTrait;

    private ManagerRegistry|MockObject $registry;
    private FeatureChecker|MockObject $featureChecker;

    private ProductPriceRemoveListener $listener;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->listener = new ProductPriceRemoveListener($this->registry);
        $this->listener->setFeatureChecker($this->featureChecker);
        $this->listener->addFeature('oro_price_lists_combined');
    }

    public function testOnRemoveAfterWithoutPriceList(): void
    {
        $event = new ProductPricesRemoveAfter([]);

        $this->registry->expects($this->never())
            ->method('getRepository');

        $this->listener->onRemoveAfter($event);
    }

    public function testOnRemoveAfterWhenFeatureDisabled(): void
    {
        $priceList = $this->createPriceList(1);
        $event = new ProductPricesRemoveAfter(['priceList' => $priceList]);

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('oro_price_lists_combined')
            ->willReturn(false);

        $this->registry->expects($this->never())
            ->method('getRepository');

        $this->listener->onRemoveAfter($event);
    }

    public function testOnRemoveAfterWithNoCombinedPriceLists(): void
    {
        $priceList = $this->createPriceList(1);
        $event = new ProductPricesRemoveAfter(['priceList' => $priceList]);

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('oro_price_lists_combined')
            ->willReturn(true);

        $cpl2plRepo = $this->createMock(CombinedPriceListToPriceListRepository::class);
        $cpl2plRepo->expects($this->once())
            ->method('getCombinedPriceListsByActualPriceLists')
            ->with([$priceList])
            ->willReturn([]);

        $cplRepo = $this->createMock(CombinedPriceListRepository::class);
        $cplRepo->expects($this->never())
            ->method('setAsNotCalculated');

        $this->registry->expects($this->exactly(2))
            ->method('getRepository')
            ->willReturnMap([
                [CombinedPriceListToPriceList::class, null, $cpl2plRepo],
                [CombinedPriceList::class, null, $cplRepo],
            ]);

        $this->listener->onRemoveAfter($event);
    }

    public function testOnRemoveAfterWithCombinedPriceListsLessThanBatchSize(): void
    {
        $priceList = $this->createPriceList(1);
        $event = new ProductPricesRemoveAfter(['priceList' => $priceList]);

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('oro_price_lists_combined')
            ->willReturn(true);

        $cpl1 = $this->createCombinedPriceList(1);
        $cpl2 = $this->createCombinedPriceList(2);
        $cpl3 = $this->createCombinedPriceList(3);

        $cpl2plRepo = $this->createMock(CombinedPriceListToPriceListRepository::class);
        $cpl2plRepo->expects($this->once())
            ->method('getCombinedPriceListsByActualPriceLists')
            ->with([$priceList])
            ->willReturn([$cpl1, $cpl2, $cpl3]);

        $cplRepo = $this->createMock(CombinedPriceListRepository::class);
        $cplRepo->expects($this->once())
            ->method('setAsNotCalculated')
            ->with([1, 2, 3]);

        $this->registry->expects($this->exactly(2))
            ->method('getRepository')
            ->willReturnMap([
                [CombinedPriceListToPriceList::class, null, $cpl2plRepo],
                [CombinedPriceList::class, null, $cplRepo],
            ]);

        $this->listener->onRemoveAfter($event);
    }

    public function testOnRemoveAfterWithCombinedPriceListsExactlyBatchSize(): void
    {
        $batchSize = 3;
        $priceList = $this->createPriceList(1);
        $event = new ProductPricesRemoveAfter(['priceList' => $priceList]);

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('oro_price_lists_combined')
            ->willReturn(true);

        $combinedPriceLists = [];
        $expectedIds = [];
        for ($i = 1; $i <= $batchSize; $i++) {
            $combinedPriceLists[] = $this->createCombinedPriceList($i);
            $expectedIds[] = $i;
        }

        $cpl2plRepo = $this->createMock(CombinedPriceListToPriceListRepository::class);
        $cpl2plRepo->expects($this->once())
            ->method('getCombinedPriceListsByActualPriceLists')
            ->with([$priceList])
            ->willReturn($combinedPriceLists);

        $cplRepo = $this->createMock(CombinedPriceListRepository::class);
        $cplRepo->expects($this->once())
            ->method('setAsNotCalculated')
            ->with($expectedIds);

        $this->registry->expects($this->exactly(2))
            ->method('getRepository')
            ->willReturnMap([
                [CombinedPriceListToPriceList::class, null, $cpl2plRepo],
                [CombinedPriceList::class, null, $cplRepo],
            ]);

        $this->listener->setBatchSize($batchSize);
        $this->listener->onRemoveAfter($event);
    }

    public function testOnRemoveAfterWithCombinedPriceListsMoreThanBatchSize(): void
    {
        $batchSize = 3;
        $priceList = $this->createPriceList(1);
        $event = new ProductPricesRemoveAfter(['priceList' => $priceList]);

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('oro_price_lists_combined')
            ->willReturn(true);

        $combinedPriceLists = [];
        $firstBatch = [];
        $secondBatch = [];

        for ($i = 1; $i <= $batchSize * 2 + 1; $i++) {
            $combinedPriceLists[] = $this->createCombinedPriceList($i);
            if ($i <= $batchSize) {
                $firstBatch[] = $i;
            } elseif ($i <= $batchSize * 2) {
                $secondBatch[] = $i;
            } else {
                $thirdBatch[] = $i;
            }
        }

        $cpl2plRepo = $this->createMock(CombinedPriceListToPriceListRepository::class);
        $cpl2plRepo->expects($this->once())
            ->method('getCombinedPriceListsByActualPriceLists')
            ->with([$priceList])
            ->willReturn($combinedPriceLists);

        $cplRepo = $this->createMock(CombinedPriceListRepository::class);
        $cplRepo->expects($this->exactly(3))
            ->method('setAsNotCalculated')
            ->withConsecutive(
                [$firstBatch],
                [$secondBatch],
                [$thirdBatch]
            );

        $this->registry->expects($this->exactly(2))
            ->method('getRepository')
            ->willReturnMap([
                [CombinedPriceListToPriceList::class, null, $cpl2plRepo],
                [CombinedPriceList::class, null, $cplRepo],
            ]);

        $this->listener->setBatchSize($batchSize);
        $this->listener->onRemoveAfter($event);
    }

    private function createPriceList(int $id): PriceList
    {
        return $this->getEntity(PriceList::class, ['id' => $id]);
    }

    private function createCombinedPriceList(int $id): CombinedPriceList
    {
        return $this->getEntity(CombinedPriceList::class, ['id' => $id]);
    }
}
