<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToPriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Handler\CombinedPriceListBuildTriggerHandler;
use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerHandler;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CombinedPriceListBuildTriggerHandlerTest extends TestCase
{
    private ManagerRegistry|MockObject $doctrine;
    private PriceListRelationTriggerHandler|MockObject $priceListRelationTriggerHandler;
    private ShardManager|MockObject $shardManager;
    private CombinedPriceListBuildTriggerHandler $combinedPriceListBuildTriggerHandler;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->priceListRelationTriggerHandler = $this->createMock(PriceListRelationTriggerHandler::class);
        $this->shardManager = $this->createMock(ShardManager::class);

        $this->combinedPriceListBuildTriggerHandler = new CombinedPriceListBuildTriggerHandler(
            $this->doctrine,
            $this->priceListRelationTriggerHandler,
            $this->shardManager
        );
    }

    /**
     * @dataProvider isSupportedDataProvider
     */
    public function testHandleIsSupported(bool $hasCombinedPriceListWithPriceList, bool $hasPrices): void
    {
        $priceList = new PriceList();
        $this->setDoctrineExpectations($priceList, $hasCombinedPriceListWithPriceList, $hasPrices);

        $this->priceListRelationTriggerHandler->expects(self::once())
            ->method('handlePriceListStatusChange')
            ->with($priceList);

        self::assertTrue($this->combinedPriceListBuildTriggerHandler->handle($priceList));
    }

    public function isSupportedDataProvider(): array
    {
        return [
            'Price list included to combined price list and price not exists' => [
                'hasCombinedPriceListWithPriceList' => true,
                'hasPrices' => false
            ],
            'Price list not included to combined price list and price exists' => [
                'hasCombinedPriceListWithPriceList' => false,
                'hasPrices' => true
            ],
        ];
    }

    /**
     * @dataProvider isNotSupportedDataProvider
     */
    public function testHandleIsNotSupported(bool $hasCombinedPriceListWithPriceList, bool $hasPrices): void
    {
        $priceList = new PriceList();
        $this->setDoctrineExpectations($priceList, $hasCombinedPriceListWithPriceList, $hasPrices);

        $this->priceListRelationTriggerHandler->expects(self::never())
            ->method('handlePriceListStatusChange')
            ->with($priceList);

        self::assertFalse($this->combinedPriceListBuildTriggerHandler->handle($priceList));
    }

    public function isNotSupportedDataProvider(): array
    {
        return [
            'Price list included to combined price list and price exists' => [
                'hasCombinedPriceListWithPriceList' => true,
                'hasPrices' => true
            ],
            'Price list not included to combined price list and price not exists' => [
                'hasCombinedPriceListWithPriceList' => false,
                'hasPrices' => false
            ],
        ];
    }

    public function testHandlePriceCreation(): void
    {
        $priceList = new PriceList();
        $priceList->setActive(true);
        ReflectionUtil::setId($priceList, 2);
        $productPrice = new ProductPrice();
        $productPrice->setId(3);
        $productPrice->setPriceList($priceList);

        $combinedPriceToPriceListRepository = $this->createMock(CombinedPriceListToPriceListRepository::class);
        $combinedPriceToPriceListRepository->expects(self::once())
            ->method('hasCombinedPriceListWithPriceList')
            ->with($priceList)
            ->willReturn(true);

        $productPriceRepository = $this->createMock(ProductPriceRepository::class);
        $productPriceRepository->expects(self::once())
            ->method('isFirstPriceAdded')
            ->with($this->shardManager, $productPrice)
            ->willReturn(true);

        $this->doctrine->expects(self::any())
            ->method('getRepository')
            ->withConsecutive([CombinedPriceListToPriceList::class], [ProductPrice::class])
            ->willReturnOnConsecutiveCalls($combinedPriceToPriceListRepository, $productPriceRepository);

        $this->priceListRelationTriggerHandler->expects(self::once())
            ->method('handlePriceListStatusChange')
            ->with($priceList);

        self::assertTrue($this->combinedPriceListBuildTriggerHandler->handlePriceCreation($productPrice));
    }

    public function testHandlePriceCreationInactiveProduct(): void
    {
        $priceList = new PriceList();
        $priceList->setActive(false);
        ReflectionUtil::setId($priceList, 2);
        $productPrice = new ProductPrice();
        $productPrice->setId(3);
        $productPrice->setPriceList($priceList);

        $combinedPriceToPriceListRepository = $this->createMock(CombinedPriceListToPriceListRepository::class);
        $combinedPriceToPriceListRepository->expects(self::never())
            ->method('hasCombinedPriceListWithPriceList')
            ->with($priceList)
            ->willReturn(true);

        $productPriceRepository = $this->createMock(ProductPriceRepository::class);
        $productPriceRepository->expects(self::never())
            ->method('isFirstPriceAdded')
            ->with($this->shardManager, $productPrice)
            ->willReturn(true);

        $this->doctrine->expects(self::exactly(2))
            ->method('getRepository')
            ->withConsecutive([CombinedPriceListToPriceList::class], [ProductPrice::class])
            ->willReturnOnConsecutiveCalls($combinedPriceToPriceListRepository, $productPriceRepository);

        $this->priceListRelationTriggerHandler->expects(self::never())
            ->method('handlePriceListStatusChange')
            ->with($priceList);

        self::assertFalse($this->combinedPriceListBuildTriggerHandler->handlePriceCreation($productPrice));
    }

    public function testHandlePriceCreationHasNoCombinedPriceListWithPriceList(): void
    {
        $priceList = new PriceList();
        $priceList->setActive(true);
        ReflectionUtil::setId($priceList, 2);
        $productPrice = new ProductPrice();
        $productPrice->setId(3);
        $productPrice->setPriceList($priceList);

        $combinedPriceToPriceListRepository = $this->createMock(CombinedPriceListToPriceListRepository::class);
        $combinedPriceToPriceListRepository->expects(self::once())
            ->method('hasCombinedPriceListWithPriceList')
            ->with($priceList)
            ->willReturn(false);

        $productPriceRepository = $this->createMock(ProductPriceRepository::class);
        $productPriceRepository->expects(self::never())
            ->method('isFirstPriceAdded')
            ->with($this->shardManager, $productPrice)
            ->willReturn(true);

        $this->doctrine->expects(self::any())
            ->method('getRepository')
            ->withConsecutive([CombinedPriceListToPriceList::class], [ProductPrice::class])
            ->willReturnOnConsecutiveCalls($combinedPriceToPriceListRepository, $productPriceRepository);

        $this->priceListRelationTriggerHandler->expects(self::never())
            ->method('handlePriceListStatusChange')
            ->with($priceList);

        self::assertFalse($this->combinedPriceListBuildTriggerHandler->handlePriceCreation($productPrice));
    }

    public function testHandlePriceCreationHasNoFirstPrice(): void
    {
        $priceList = new PriceList();
        $priceList->setActive(true);
        ReflectionUtil::setId($priceList, 2);
        $productPrice = new ProductPrice();
        $productPrice->setId(3);
        $productPrice->setPriceList($priceList);

        $combinedPriceToPriceListRepository = $this->createMock(CombinedPriceListToPriceListRepository::class);
        $combinedPriceToPriceListRepository->expects(self::once())
            ->method('hasCombinedPriceListWithPriceList')
            ->with($priceList)
            ->willReturn(true);

        $productPriceRepository = $this->createMock(ProductPriceRepository::class);
        $productPriceRepository->expects(self::once())
            ->method('isFirstPriceAdded')
            ->with($this->shardManager, $productPrice)
            ->willReturn(false);

        $this->doctrine->expects(self::any())
            ->method('getRepository')
            ->withConsecutive([CombinedPriceListToPriceList::class], [ProductPrice::class])
            ->willReturnOnConsecutiveCalls($combinedPriceToPriceListRepository, $productPriceRepository);

        $this->priceListRelationTriggerHandler->expects(self::never())
            ->method('handlePriceListStatusChange')
            ->with($priceList);

        self::assertFalse($this->combinedPriceListBuildTriggerHandler->handlePriceCreation($productPrice));
    }

    public function testHandleMassPriceCreation(): void
    {
        $priceList1 = new PriceList();
        $priceList1->setActive(true);
        ReflectionUtil::setId($priceList1, 1);

        $priceList2 = new PriceList();
        $priceList2->setActive(true);
        ReflectionUtil::setId($priceList2, 2);

        $productPrice1 = new ProductPrice();
        $productPrice1->setId(1);
        $productPrice1->setPriceList($priceList1);

        $productPrice2 = new ProductPrice();
        $productPrice2->setId(2);
        $productPrice2->setPriceList($priceList1);

        $productPrice3 = new ProductPrice();
        $productPrice3->setId(3);
        $productPrice3->setPriceList($priceList2);

        $productPrices = [$productPrice1, $productPrice2, $productPrice3];

        $combinedPriceToPriceListRepository = $this->createMock(CombinedPriceListToPriceListRepository::class);
        $combinedPriceToPriceListRepository->expects(self::exactly(2))
            ->method('hasCombinedPriceListWithPriceList')
            ->withConsecutive([$priceList1], [$priceList2])
            ->willReturn(true);

        $productPriceRepository = $this->createMock(ProductPriceRepository::class);
        $productPriceRepository->expects(self::exactly(2))
            ->method('areAllPricesNewInPriceList')
            ->withConsecutive(
                [$this->shardManager, $priceList1, [$productPrice1, $productPrice2]],
                [$this->shardManager, $priceList2, [$productPrice3]]
            )
            ->willReturn(true);

        $this->doctrine->expects(self::any())
            ->method('getRepository')
            ->willReturnMap([
                [CombinedPriceListToPriceList::class, null, $combinedPriceToPriceListRepository],
                [ProductPrice::class, null, $productPriceRepository]
            ]);

        $this->priceListRelationTriggerHandler->expects(self::exactly(2))
            ->method('handlePriceListStatusChange')
            ->withConsecutive([$priceList1], [$priceList2]);

        self::assertTrue($this->combinedPriceListBuildTriggerHandler->handleMassPriceCreation($productPrices));
    }

    public function testHandleMassPriceCreationWithInactivePriceList(): void
    {
        $priceList = new PriceList();
        $priceList->setActive(false);
        ReflectionUtil::setId($priceList, 1);

        $productPrice = new ProductPrice();
        $productPrice->setId(1);
        $productPrice->setPriceList($priceList);

        $productPrices = [$productPrice];

        $combinedPriceToPriceListRepository = $this->createMock(CombinedPriceListToPriceListRepository::class);
        $combinedPriceToPriceListRepository->expects(self::never())
            ->method('hasCombinedPriceListWithPriceList');

        $productPriceRepository = $this->createMock(ProductPriceRepository::class);
        $productPriceRepository->expects(self::never())
            ->method('areAllPricesNewInPriceList');

        $this->doctrine->expects(self::any())
            ->method('getRepository')
            ->willReturnMap([
                [CombinedPriceListToPriceList::class, null, $combinedPriceToPriceListRepository],
                [ProductPrice::class, null, $productPriceRepository]
            ]);

        $this->priceListRelationTriggerHandler->expects(self::never())
            ->method('handlePriceListStatusChange');

        self::assertFalse($this->combinedPriceListBuildTriggerHandler->handleMassPriceCreation($productPrices));
    }

    public function testHandleMassPriceCreationWithNoCombinedPriceList(): void
    {
        $priceList = new PriceList();
        $priceList->setActive(true);
        ReflectionUtil::setId($priceList, 1);

        $productPrice = new ProductPrice();
        $productPrice->setId(1);
        $productPrice->setPriceList($priceList);

        $productPrices = [$productPrice];

        $combinedPriceToPriceListRepository = $this->createMock(CombinedPriceListToPriceListRepository::class);
        $combinedPriceToPriceListRepository->expects(self::once())
            ->method('hasCombinedPriceListWithPriceList')
            ->with($priceList)
            ->willReturn(false);

        $productPriceRepository = $this->createMock(ProductPriceRepository::class);
        $productPriceRepository->expects(self::never())
            ->method('areAllPricesNewInPriceList');

        $this->doctrine->expects(self::any())
            ->method('getRepository')
            ->willReturnMap([
                [CombinedPriceListToPriceList::class, null, $combinedPriceToPriceListRepository],
                [ProductPrice::class, null, $productPriceRepository]
            ]);

        $this->priceListRelationTriggerHandler->expects(self::never())
            ->method('handlePriceListStatusChange');

        self::assertFalse($this->combinedPriceListBuildTriggerHandler->handleMassPriceCreation($productPrices));
    }

    public function testHandleMassPriceCreationWithNotAllPricesNew(): void
    {
        $priceList = new PriceList();
        $priceList->setActive(true);
        ReflectionUtil::setId($priceList, 1);

        $productPrice = new ProductPrice();
        $productPrice->setId(1);
        $productPrice->setPriceList($priceList);

        $productPrices = [$productPrice];

        $combinedPriceToPriceListRepository = $this->createMock(CombinedPriceListToPriceListRepository::class);
        $combinedPriceToPriceListRepository->expects(self::once())
            ->method('hasCombinedPriceListWithPriceList')
            ->with($priceList)
            ->willReturn(true);

        $productPriceRepository = $this->createMock(ProductPriceRepository::class);
        $productPriceRepository->expects(self::once())
            ->method('areAllPricesNewInPriceList')
            ->with($this->shardManager, $priceList, [$productPrice])
            ->willReturn(false);

        $this->doctrine->expects(self::any())
            ->method('getRepository')
            ->willReturnMap([
                [CombinedPriceListToPriceList::class, null, $combinedPriceToPriceListRepository],
                [ProductPrice::class, null, $productPriceRepository]
            ]);

        $this->priceListRelationTriggerHandler->expects(self::never())
            ->method('handlePriceListStatusChange');

        self::assertFalse($this->combinedPriceListBuildTriggerHandler->handleMassPriceCreation($productPrices));
    }

    public function testHandleMassPriceCreationWithEmptyArray(): void
    {
        $productPrices = [];

        $combinedPriceToPriceListRepository = $this->createMock(CombinedPriceListToPriceListRepository::class);
        $combinedPriceToPriceListRepository->expects(self::never())
            ->method('hasCombinedPriceListWithPriceList');

        $productPriceRepository = $this->createMock(ProductPriceRepository::class);
        $productPriceRepository->expects(self::never())
            ->method('areAllPricesNewInPriceList');

        $this->doctrine->expects(self::any())
            ->method('getRepository')
            ->willReturnMap([
                [CombinedPriceListToPriceList::class, null, $combinedPriceToPriceListRepository],
                [ProductPrice::class, null, $productPriceRepository]
            ]);

        $this->priceListRelationTriggerHandler->expects(self::never())
            ->method('handlePriceListStatusChange');

        self::assertFalse($this->combinedPriceListBuildTriggerHandler->handleMassPriceCreation($productPrices));
    }

    public function testHandleMassPriceCreationMixedScenarios(): void
    {
        // Price list 1: Active, has combined price list, all prices are new -> should trigger rebuild
        $priceList1 = new PriceList();
        $priceList1->setActive(true);
        ReflectionUtil::setId($priceList1, 1);

        // Price list 2: Active, has combined price list, but not all prices are new -> should NOT trigger rebuild
        $priceList2 = new PriceList();
        $priceList2->setActive(true);
        ReflectionUtil::setId($priceList2, 2);

        // Price list 3: Inactive -> should NOT trigger rebuild
        $priceList3 = new PriceList();
        $priceList3->setActive(false);
        ReflectionUtil::setId($priceList3, 3);

        $productPrice1 = new ProductPrice();
        $productPrice1->setId(1);
        $productPrice1->setPriceList($priceList1);

        $productPrice2 = new ProductPrice();
        $productPrice2->setId(2);
        $productPrice2->setPriceList($priceList2);

        $productPrice3 = new ProductPrice();
        $productPrice3->setId(3);
        $productPrice3->setPriceList($priceList3);

        $productPrices = [$productPrice1, $productPrice2, $productPrice3];

        $combinedPriceToPriceListRepository = $this->createMock(CombinedPriceListToPriceListRepository::class);
        $combinedPriceToPriceListRepository->expects(self::exactly(2))
            ->method('hasCombinedPriceListWithPriceList')
            ->withConsecutive([$priceList1], [$priceList2])
            ->willReturn(true);

        $productPriceRepository = $this->createMock(ProductPriceRepository::class);
        $productPriceRepository->expects(self::exactly(2))
            ->method('areAllPricesNewInPriceList')
            ->withConsecutive(
                [$this->shardManager, $priceList1, [$productPrice1]],
                [$this->shardManager, $priceList2, [$productPrice2]]
            )
            ->willReturnOnConsecutiveCalls(true, false);

        $this->doctrine->expects(self::any())
            ->method('getRepository')
            ->willReturnMap([
                [CombinedPriceListToPriceList::class, null, $combinedPriceToPriceListRepository],
                [ProductPrice::class, null, $productPriceRepository]
            ]);

        // Only price list 1 should trigger rebuild
        $this->priceListRelationTriggerHandler->expects(self::once())
            ->method('handlePriceListStatusChange')
            ->with($priceList1);

        self::assertTrue($this->combinedPriceListBuildTriggerHandler->handleMassPriceCreation($productPrices));
    }

    private function setDoctrineExpectations(
        PriceList $priceList,
        bool $hasCombinedPriceListWithPriceList,
        bool $hasPrices
    ): void {
        $combinedPriceListToPriceListRepository = $this->createMock(CombinedPriceListToPriceListRepository::class);
        $combinedPriceListToPriceListRepository->expects(self::once())
            ->method('hasCombinedPriceListWithPriceList')
            ->with($priceList)
            ->willReturn($hasCombinedPriceListWithPriceList);

        $productPriceRepository = $this->createMock(ProductPriceRepository::class);
        $productPriceRepository->expects(self::once())
            ->method('hasPrices')
            ->with($this->shardManager, $priceList)
            ->willReturn($hasPrices);

        $this->doctrine->expects(self::any())
            ->method('getRepository')
            ->willReturnMap([
                [CombinedPriceListToPriceList::class, null, $combinedPriceListToPriceListRepository],
                [ProductPrice::class, null, $productPriceRepository]
            ]);
    }
}
