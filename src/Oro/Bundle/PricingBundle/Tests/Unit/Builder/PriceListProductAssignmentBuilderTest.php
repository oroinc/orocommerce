<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Builder;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\PricingBundle\Builder\PriceListProductAssignmentBuilder;
use Oro\Bundle\PricingBundle\Compiler\ProductAssignmentRuleCompiler;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToProduct;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\BasePriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToProductRepository;
use Oro\Bundle\PricingBundle\Event\AssignmentBuilderBuildEvent;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PriceListProductAssignmentBuilderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ShardManager|\PHPUnit\Framework\MockObject\MockObject */
    private $shardManager;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var InsertFromSelectQueryExecutor|\PHPUnit\Framework\MockObject\MockObject */
    private $insertFromSelectQueryExecutor;

    /** @var ProductAssignmentRuleCompiler|\PHPUnit\Framework\MockObject\MockObject */
    private $ruleCompiler;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var PriceListProductAssignmentBuilder */
    private $priceListProductAssignmentBuilder;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->insertFromSelectQueryExecutor = $this->createMock(InsertFromSelectQueryExecutor::class);
        $this->ruleCompiler = $this->createMock(ProductAssignmentRuleCompiler::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->shardManager = $this->createMock(ShardManager::class);

        $this->priceListProductAssignmentBuilder = new PriceListProductAssignmentBuilder(
            $this->registry,
            $this->insertFromSelectQueryExecutor,
            $this->ruleCompiler,
            $this->eventDispatcher,
            $this->shardManager
        );
    }

    public function testBuildByPriceListNoAssignmentRules()
    {
        $priceList = $this->createMock(PriceList::class);

        $this->assertClearGeneratedPricesCall($priceList);
        $this->insertFromSelectQueryExecutor->expects($this->never())
            ->method($this->anything());

        $this->priceListProductAssignmentBuilder->buildByPriceList($priceList);
    }

    public function testBuildByPriceList()
    {
        $fields = ['product', 'priceList', 'manual'];

        $priceList = $this->createMock(PriceList::class);
        $priceList->expects($this->once())
            ->method('getProductAssignmentRule')
            ->willReturn('product.id < 100');

        $this->assertClearGeneratedPricesCall($priceList);

        $qb = $this->createMock(QueryBuilder::class);

        $this->ruleCompiler->expects($this->once())
            ->method('getOrderedFields')
            ->willReturn($fields);

        $this->ruleCompiler->expects($this->once())
            ->method('compile')
            ->with($priceList)
            ->willReturn($qb);

        $this->insertFromSelectQueryExecutor->expects($this->once())
            ->method('execute')
            ->with(PriceListToProduct::class, $fields, $qb);

        $event = new AssignmentBuilderBuildEvent($priceList);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($event, AssignmentBuilderBuildEvent::NAME);

        $this->priceListProductAssignmentBuilder->buildByPriceList($priceList);
    }

    public function testBuildByPriceListForProduct()
    {
        $fields = ['product', 'priceList', 'manual'];
        $productId = 1;

        $priceList = $this->createMock(PriceList::class);
        $priceList->expects($this->once())
            ->method('getProductAssignmentRule')
            ->willReturn('product.id < 100');

        $this->assertClearGeneratedPricesCall($priceList);

        $qb = $this->createMock(QueryBuilder::class);

        $this->ruleCompiler->expects($this->once())
            ->method('getOrderedFields')
            ->willReturn($fields);

        $this->ruleCompiler->expects($this->once())
            ->method('compile')
            ->with($priceList, [$productId])
            ->willReturn($qb);

        $this->insertFromSelectQueryExecutor->expects($this->once())
            ->method('execute')
            ->with(PriceListToProduct::class, $fields, $qb);

        $event = new AssignmentBuilderBuildEvent($priceList, [$productId]);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($event, AssignmentBuilderBuildEvent::NAME);

        $this->priceListProductAssignmentBuilder->buildByPriceList($priceList, [$productId]);
    }

    private function assertClearGeneratedPricesCall(PriceList $priceList): void
    {
        $priceListToProductRepository = $this->createMock(PriceListToProductRepository::class);
        $priceListToProductRepository->expects($this->once())
            ->method('deleteGeneratedRelations')
            ->with($priceList);

        $repoProductPrice = $this->createMock(BasePriceListRepository::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->exactly(2))
            ->method('getRepository')
            ->willReturnMap([
                [PriceListToProduct::class, $priceListToProductRepository],
                [ProductPrice::class, $repoProductPrice],
            ]);

        $this->registry->expects($this->exactly(2))
            ->method('getManagerForClass')
            ->willReturn($em);
    }

    public function testBuildByPriceListWithoutTriggers()
    {
        $fields = ['product', 'priceList', 'manual'];
        $this->ruleCompiler->expects($this->once())
            ->method('getOrderedFields')
            ->willReturn($fields);

        $priceList = $this->createMock(PriceList::class);
        $priceList->expects($this->once())
            ->method('getProductAssignmentRule')
            ->willReturn('product.id < 100');
        $this->assertClearGeneratedPricesCall($priceList);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $this->ruleCompiler->expects($this->once())
            ->method('compile')
            ->with($priceList)
            ->willReturn($queryBuilder);

        $this->insertFromSelectQueryExecutor->expects($this->once())
            ->method('execute')
            ->with(PriceListToProduct::class, $fields, $queryBuilder);

        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');

        $this->priceListProductAssignmentBuilder->buildByPriceListWithoutEventDispatch($priceList);
    }
}
