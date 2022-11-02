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

class CombinedPriceListBuildTriggerHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var PriceListRelationTriggerHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $priceListRelationTriggerHandler;

    /** @var ShardManager|\PHPUnit\Framework\MockObject\MockObject */
    private $shardManager;

    /** @var CombinedPriceListBuildTriggerHandler */
    private $combinedPriceListBuildTriggerHandler;

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

        $this->priceListRelationTriggerHandler->expects($this->once())
            ->method('handlePriceListStatusChange')
            ->with($priceList);

        $this->assertTrue($this->combinedPriceListBuildTriggerHandler->handle($priceList));
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

        $this->priceListRelationTriggerHandler->expects($this->never())
            ->method('handlePriceListStatusChange')
            ->with($priceList);

        $this->assertFalse($this->combinedPriceListBuildTriggerHandler->handle($priceList));
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

    private function setDoctrineExpectations(
        PriceList $priceList,
        bool $hasCombinedPriceListWithPriceList,
        bool $hasPrices
    ): void {
        $combinedPriceListToPriceListRepository = $this->createMock(CombinedPriceListToPriceListRepository::class);
        $combinedPriceListToPriceListRepository->expects($this->once())
            ->method('hasCombinedPriceListWithPriceList')
            ->with($priceList)
            ->willReturn($hasCombinedPriceListWithPriceList);

        $productPriceRepository = $this->createMock(ProductPriceRepository::class);
        $productPriceRepository->expects($this->once())
            ->method('hasPrices')
            ->with($this->shardManager, $priceList)
            ->willReturn($hasPrices);

        $this->doctrine->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [CombinedPriceListToPriceList::class, null, $combinedPriceListToPriceListRepository],
                [ProductPrice::class, null, $productPriceRepository]
            ]);
    }
}
