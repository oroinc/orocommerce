<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Builder;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Builder\ProductPriceBuilder;
use Oro\Bundle\PricingBundle\Compiler\PriceListRuleCompiler;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerHandler;
use Oro\Bundle\ProductBundle\Entity\Product;

class ProductPriceBuilderTest extends \PHPUnit_Framework_TestCase
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
     * @var PriceListRuleCompiler|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $ruleCompiler;

    /**
     * @var PriceListTriggerHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $priceListTriggerHandler;

    /**
     * @var ProductPriceBuilder
     */
    protected $productPriceBuilder;

    protected function setUp()
    {
        $this->registry = $this->getMock(ManagerRegistry::class);
        $this->insertFromSelectQueryExecutor = $this->getMockBuilder(InsertFromSelectQueryExecutor::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->ruleCompiler = $this->getMockBuilder(PriceListRuleCompiler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->priceListTriggerHandler = $this->getMockBuilder(PriceListTriggerHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productPriceBuilder = new ProductPriceBuilder(
            $this->registry,
            $this->insertFromSelectQueryExecutor,
            $this->ruleCompiler,
            $this->priceListTriggerHandler
        );
    }

    public function testBuildByPriceListNoRules()
    {
        /** @var PriceList|\PHPUnit_Framework_MockObject_MockObject $priceList * */
        $priceList = $this->getMock(PriceList::class);

        /** @var Product|\PHPUnit_Framework_MockObject_MockObject $product * */
        $product = $this->getMock(Product::class);

        $repo = $this->getRepositoryMock();
        $repo->expects($this->once())
            ->method('deleteGeneratedPrices')
            ->with($priceList, $product);

        $this->insertFromSelectQueryExecutor->expects($this->never())
            ->method($this->anything());

        $this->priceListTriggerHandler->expects($this->once())
            ->method('addTriggersForPriceList')
            ->with(Topics::PRICE_LIST_CHANGE, $priceList, $product);

        $this->productPriceBuilder->buildByPriceList($priceList, $product);
    }

    public function testBuildByPriceListNoRulesWithoutProduct()
    {
        /** @var PriceList|\PHPUnit_Framework_MockObject_MockObject $priceList * */
        $priceList = $this->getMock(PriceList::class);

        $repo = $this->getRepositoryMock();
        $repo->expects($this->once())
            ->method('deleteGeneratedPrices')
            ->with($priceList, null);

        $this->insertFromSelectQueryExecutor->expects($this->never())
            ->method($this->anything());

        $product = null;
        $this->priceListTriggerHandler->expects($this->once())
            ->method('addTriggersForPriceList')
            ->with(Topics::PRICE_LIST_CHANGE, $priceList, $product);

        $this->productPriceBuilder->buildByPriceList($priceList);
    }

    public function testBuildByPriceList()
    {
        $priceList = new PriceList();

        /** @var Product|\PHPUnit_Framework_MockObject_MockObject $product * */
        $product = $this->getMock(Product::class);

        $rule1 = new PriceRule();
        $rule1->setPriority(10);
        $rule2 = new PriceRule();
        $rule2->setPriority(20);

        $priceList->setPriceRules(new ArrayCollection([$rule2, $rule1]));

        $fields = ['field1', 'field2'];

        $repo = $this->getRepositoryMock();
        $repo->expects($this->once())
            ->method('deleteGeneratedPrices')
            ->with($priceList, $product);

        $qb = $this->assertInsertCall($fields, [$rule1, $rule2], $product);

        $this->insertFromSelectQueryExecutor->expects($this->exactly(2))
            ->method('execute')
            ->with(
                ProductPrice::class,
                $fields,
                $qb
            );

        $this->priceListTriggerHandler->expects($this->once())
            ->method('addTriggersForPriceList')
            ->with(Topics::PRICE_LIST_CHANGE, $priceList, $product);

        $this->productPriceBuilder->buildByPriceList($priceList, $product);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ProductPriceRepository
     */
    protected function getRepositoryMock()
    {
        $repo = $this->getMockBuilder(ProductPriceRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $em = $this->getMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(ProductPrice::class)
            ->willReturn($repo);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(ProductPrice::class)
            ->willReturn($em);

        return $repo;
    }

    /**
     * @param array $fields
     * @param array $rules
     * @param Product|null $product
     * @return QueryBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function assertInsertCall(array $fields, array $rules, Product $product = null)
    {
        $rulesCount = count($rules);

        $qb = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->ruleCompiler->expects($this->exactly($rulesCount))
            ->method('getOrderedFields')
            ->willReturn($fields);

        $this->ruleCompiler->expects($this->exactly($rulesCount))
            ->method('compile')
            ->willReturn($qb);

        $c = 1;
        foreach ($rules as $rule) {
            $this->ruleCompiler->expects($this->at($c))
                ->method('compile')
                ->with($rule, $product);
            $c += 2;
        }

        return $qb;
    }
}
