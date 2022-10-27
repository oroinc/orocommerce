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
    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $registry;

    /**
     * @var ObjectManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $manager;

    /**
     * @var InsertFromSelectQueryExecutor|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $insertQueryExecutor;

    /**
     * @var PriceListToProductDuplicator
     */
    protected $priceListToProductDuplicator;

    protected function setUp(): void
    {
        $this->manager = $this->getMockBuilder(ObjectManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->insertQueryExecutor = $this->getMockBuilder(InsertFromSelectQueryExecutor::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->priceListToProductDuplicator = new PriceListToProductDuplicator(
            $this->registry,
            $this->insertQueryExecutor
        );
    }

    public function testDuplicate()
    {
        /** @var PriceList|\PHPUnit\Framework\MockObject\MockObject $sourcePriceList **/
        $sourcePriceList = $this->createMock(PriceList::class);

        /** @var PriceList|\PHPUnit\Framework\MockObject\MockObject $targetPriceList **/
        $targetPriceList = $this->createMock(PriceList::class);

        $entityName = PriceListToProduct::class;
        $this->priceListToProductDuplicator->setEntityName($entityName);

        /** @var \PHPUnit\Framework\MockObject\MockObject $repository */
        $repository = $this->getMockBuilder(PriceListToProductRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

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
