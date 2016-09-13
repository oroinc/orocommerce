<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Builder;

use Doctrine\Common\Persistence\ManagerRegistry;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\PricingBundle\Compiler\ProductAssignmentRuleCompiler;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Builder\PriceListProductAssignmentBuilder;
use Oro\Bundle\PricingBundle\Entity\PriceListToProduct;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\BasePriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToProductRepository;
use Oro\Bundle\PricingBundle\Event\AssignmentBuilderBuildEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PriceListProductAssignmentBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var InsertFromSelectQueryExecutor|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $insertFromSelectQueryExecutor;

    /**
     * @var ProductAssignmentRuleCompiler|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $ruleCompiler;

    /**
     * @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventDispatcher;

    /**
     * @var PriceListProductAssignmentBuilder
     */
    protected $priceListProductAssignmentBuilder;

    protected function setUp()
    {
        $this->registry = $this->getMock(ManagerRegistry::class);
        $this->insertFromSelectQueryExecutor = $this->getMockBuilder(InsertFromSelectQueryExecutor::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->ruleCompiler = $this->getMockBuilder(ProductAssignmentRuleCompiler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->eventDispatcher = $this->getMock(EventDispatcherInterface::class);

        $this->priceListProductAssignmentBuilder = new PriceListProductAssignmentBuilder(
            $this->registry,
            $this->insertFromSelectQueryExecutor,
            $this->ruleCompiler,
            $this->eventDispatcher
        );
    }

    public function testBuildByPriceListNoAssignmentRules()
    {
        /** @var PriceList|\PHPUnit_Framework_MockObject_MockObject $priceList */
        $priceList = $this->getMock(PriceList::class);

        $this->assertClearGeneratedPricesCall($priceList);
        $this->insertFromSelectQueryExecutor->expects($this->never())
            ->method($this->anything());

        $this->priceListProductAssignmentBuilder->buildByPriceList($priceList);
    }

    public function testBuildByPriceList()
    {
        $fields = ['product', 'priceList', 'manual'];

        /** @var PriceList|\PHPUnit_Framework_MockObject_MockObject $priceList */
        $priceList = $this->getMock(PriceList::class);
        $priceList->expects($this->once())
            ->method('getProductAssignmentRule')
            ->willReturn('product.id < 100');

        $this->assertClearGeneratedPricesCall($priceList);

        $qb = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->ruleCompiler->expects($this->once())
            ->method('getOrderedFields')
            ->willReturn($fields);

        $this->ruleCompiler->expects($this->once())
            ->method('compile')
            ->with($priceList)
            ->willReturn($qb);

        $this->insertFromSelectQueryExecutor->expects($this->once())
            ->method('execute')
            ->with(
                PriceListToProduct::class,
                $fields,
                $qb
            );

        $event = new AssignmentBuilderBuildEvent();
        $event->setPriceList($priceList);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(AssignmentBuilderBuildEvent::NAME, $event);

        $this->priceListProductAssignmentBuilder->buildByPriceList($priceList);
    }

    /**
     * @param PriceList $priceList
     */
    protected function assertClearGeneratedPricesCall(PriceList $priceList)
    {
        $priceListToProductRepository = $this->getMockBuilder(PriceListToProductRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $priceListToProductRepository->expects($this->once())
            ->method('deleteGeneratedRelations')
            ->with($priceList);

        $repoProductPrice = $this->getMockBuilder(BasePriceListRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $priceListToProductRepository->method('deleteInvalidPrices')
            ->with($priceList);

        $em = $this->getMock(EntityManagerInterface::class);
        $em->expects($this->exactly(2))
            ->method('getRepository')
            ->willReturnMap(
                [
                    [PriceListToProduct::class, $priceListToProductRepository],
                    [ProductPrice::class, $repoProductPrice],
                ]
            );

        $this->registry->expects($this->exactly(2))
            ->method('getManagerForClass')
            ->willReturn($em);
    }
}
