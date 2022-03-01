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
        $this->assertManagerRegistry($priceList, $hasCombinedPriceListWithPriceList, $hasPrices);

        $this->priceListRelationTriggerHandler
            ->expects($this->never())
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
