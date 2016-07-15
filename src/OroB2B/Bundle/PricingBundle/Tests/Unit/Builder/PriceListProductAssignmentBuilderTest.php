<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Builder;

use Doctrine\Common\Persistence\ManagerRegistry;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;

use OroB2B\Bundle\PricingBundle\Compiler\ProductAssignmentRuleCompiler;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Builder\PriceListProductAssignmentBuilder;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToProduct;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToProductRepository;

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
        $this->priceListProductAssignmentBuilder = new PriceListProductAssignmentBuilder(
            $this->registry,
            $this->insertFromSelectQueryExecutor,
            $this->ruleCompiler
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

        $this->priceListProductAssignmentBuilder->buildByPriceList($priceList);
    }

    /**
     * @param PriceList $priceList
     */
    protected function assertClearGeneratedPricesCall(PriceList $priceList)
    {
        $repo = $this->getMockBuilder(PriceListToProductRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->once())
            ->method('deleteGeneratedRelations')
            ->with($priceList);

        $em = $this->getMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(PriceListToProduct::class)
            ->willReturn($em);
    }
}
