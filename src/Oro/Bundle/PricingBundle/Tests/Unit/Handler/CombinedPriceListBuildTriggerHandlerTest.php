<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Handler;

use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToPriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Handler\CombinedPriceListBuildTriggerHandler;
use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerHandler;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class CombinedPriceListBuildTriggerHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var CombinedPriceListBuildTriggerHandler */
    private $combinedPriceListBuildTriggerHandler;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $managerRegistry;

    /** @var PriceListRelationTriggerHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $priceListRelationTriggerHandler;

    /** @var ShardManager|\PHPUnit\Framework\MockObject\MockObject */
    private $shardManager;

    protected function setUp(): void
    {
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->priceListRelationTriggerHandler = $this->createMock(PriceListRelationTriggerHandler::class);
        $this->shardManager = $this->createMock(ShardManager::class);

        $this->combinedPriceListBuildTriggerHandler = new CombinedPriceListBuildTriggerHandler(
            $this->managerRegistry,
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
        $this->assertManagerRegistry($priceList, $hasCombinedPriceListWithPriceList, $hasPrices);

        $this->priceListRelationTriggerHandler
            ->expects($this->once())
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
        $this->assertManagerRegistry($priceList, $hasCombinedPriceListWithPriceList, $hasPrices);

        $this->priceListRelationTriggerHandler
            ->expects($this->never())
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

        $this->managerRegistry->expects(self::any())
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

        $this->managerRegistry->expects(self::exactly(2))
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

        $this->managerRegistry->expects(self::any())
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

        $this->managerRegistry->expects(self::any())
            ->method('getRepository')
            ->withConsecutive([CombinedPriceListToPriceList::class], [ProductPrice::class])
            ->willReturnOnConsecutiveCalls($combinedPriceToPriceListRepository, $productPriceRepository);

        $this->priceListRelationTriggerHandler->expects(self::never())
            ->method('handlePriceListStatusChange')
            ->with($priceList);

        self::assertFalse($this->combinedPriceListBuildTriggerHandler->handlePriceCreation($productPrice));
    }

    private function assertManagerRegistry(
        PriceList $priceList,
        bool $hasCombinedPriceListWithPriceList,
        bool $hasPrices
    ): void {
        $combinedPriceListToPriceListRepository = $this->createMock(CombinedPriceListToPriceListRepository::class);
        $combinedPriceListToPriceListRepository
            ->expects($this->once())
            ->method('hasCombinedPriceListWithPriceList')
            ->with($priceList)
            ->willReturn($hasCombinedPriceListWithPriceList);

        $productPriceRepository = $this->createMock(ProductPriceRepository::class);
        $productPriceRepository
            ->expects($this->once())
            ->method('hasPrices')
            ->with($this->shardManager, $priceList)
            ->willReturn($hasPrices);

        $this->managerRegistry
            ->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [CombinedPriceListToPriceList::class, null, $combinedPriceListToPriceListRepository],
                [ProductPrice::class, null, $productPriceRepository]
            ]);
    }
}
