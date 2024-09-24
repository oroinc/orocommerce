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

class CombinedPriceListBuildTriggerHandlerTest extends \PHPUnit\Framework\TestCase
{
    private ManagerRegistry $doctrine;
    private PriceListRelationTriggerHandler $priceListRelationTriggerHandler;
    private ShardManager $shardManager;
    private CombinedPriceListBuildTriggerHandler $combinedPriceListBuildTriggerHandler;

    #[\Override]
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
