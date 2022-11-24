<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Duplicator;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\PricingBundle\Duplicator\PriceListToProductDuplicator;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToProduct;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToProductRepository;

class PriceListToProductDuplicatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var ObjectManager|\PHPUnit\Framework\MockObject\MockObject */
    private $manager;

    /** @var InsertFromSelectQueryExecutor|\PHPUnit\Framework\MockObject\MockObject */
    private $insertQueryExecutor;

    /** @var PriceListToProductDuplicator */
    private $priceListToProductDuplicator;

    protected function setUp(): void
    {
        $this->manager = $this->createMock(ObjectManager::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->insertQueryExecutor = $this->createMock(InsertFromSelectQueryExecutor::class);

        $this->priceListToProductDuplicator = new PriceListToProductDuplicator(
            $this->registry,
            $this->insertQueryExecutor
        );
    }

    public function testDuplicate()
    {
        $sourcePriceList = $this->createMock(PriceList::class);

        $targetPriceList = $this->createMock(PriceList::class);

        $entityName = PriceListToProduct::class;
        $this->priceListToProductDuplicator->setEntityName($entityName);

        $repository = $this->createMock(PriceListToProductRepository::class);

        $this->manager->expects($this->once())
            ->method('getRepository')
            ->with($entityName)
            ->willReturn($repository);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with($entityName)
            ->willReturn($this->manager);

        $repository->expects($this->once())
            ->method('copyRelations')
            ->with($sourcePriceList, $targetPriceList, $this->insertQueryExecutor);

        $this->priceListToProductDuplicator->duplicate($sourcePriceList, $targetPriceList);
    }
}
