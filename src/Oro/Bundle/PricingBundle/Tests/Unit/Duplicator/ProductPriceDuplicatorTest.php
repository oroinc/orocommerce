<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Duplicator;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Duplicator\ProductPriceDuplicator;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\ORM\InsertFromSelectShardQueryExecutor;

class ProductPriceDuplicatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var InsertFromSelectShardQueryExecutor|\PHPUnit\Framework\MockObject\MockObject */
    private $insertExecutor;

    /** @var ProductPriceDuplicator */
    private $priceDuplicator;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->insertExecutor = $this->createMock(InsertFromSelectShardQueryExecutor::class);

        $this->priceDuplicator = new ProductPriceDuplicator($this->doctrine, $this->insertExecutor);
    }

    /**
     * @dataProvider duplicateDataProvider
     */
    public function testDuplicate(PriceList $sourcePriceList, PriceList $targetPriceList)
    {
        $priceListClass = ProductPrice::class;
        $this->priceDuplicator->setPriceListClass($priceListClass);

        $em = $this->createMock(EntityManagerInterface::class);
        $repository = $this->createMock(ProductPriceRepository::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with($priceListClass)
            ->willReturn($repository);
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($em);
        $repository->expects($this->once())
            ->method('copyPrices')
            ->with($sourcePriceList, $targetPriceList, $this->insertExecutor);

        $this->priceDuplicator->duplicate($sourcePriceList, $targetPriceList);
    }

    public function duplicateDataProvider(): array
    {
        return [
            'target price list without prices' => [
                'sourcePriceList' => new PriceList(),
                'targetPriceList' => new PriceList()
            ],
        ];
    }
}
