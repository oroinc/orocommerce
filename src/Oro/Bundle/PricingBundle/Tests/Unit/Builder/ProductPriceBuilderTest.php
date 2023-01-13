<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Builder;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\PricingBundle\Async\Topic\ResolveCombinedPriceByPriceListTopic;
use Oro\Bundle\PricingBundle\Builder\ProductPriceBuilder;
use Oro\Bundle\PricingBundle\Compiler\PriceListRuleCompiler;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Handler\CombinedPriceListBuildTriggerHandler;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerHandler;
use Oro\Bundle\PricingBundle\ORM\InsertFromSelectShardQueryExecutor;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\Testing\Unit\EntityTrait;

class ProductPriceBuilderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ShardManager|\PHPUnit\Framework\MockObject\MockObject */
    private $shardManager;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var InsertFromSelectShardQueryExecutor|\PHPUnit\Framework\MockObject\MockObject */
    private $insertFromSelectQueryExecutor;

    /** @var PriceListRuleCompiler|\PHPUnit\Framework\MockObject\MockObject */
    private $ruleCompiler;

    /** @var PriceListTriggerHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $priceListTriggerHandler;

    /**
     * @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject
     */
    private $featureChecker;

    /** @var ProductPriceBuilder */
    private $productPriceBuilder;

    /** @var CombinedPriceListBuildTriggerHandler */
    private $combinedPriceListBuildTriggerHandler;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->insertFromSelectQueryExecutor = $this->createMock(InsertFromSelectShardQueryExecutor::class);
        $this->ruleCompiler = $this->createMock(PriceListRuleCompiler::class);
        $this->priceListTriggerHandler = $this->createMock(PriceListTriggerHandler::class);
        $this->shardManager = $this->createMock(ShardManager::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->combinedPriceListBuildTriggerHandler = $this->createMock(CombinedPriceListBuildTriggerHandler::class);

        $this->productPriceBuilder = new ProductPriceBuilder(
            $this->registry,
            $this->insertFromSelectQueryExecutor,
            $this->ruleCompiler,
            $this->priceListTriggerHandler,
            $this->shardManager,
            $this->combinedPriceListBuildTriggerHandler
        );
        $this->productPriceBuilder->setFeatureChecker($this->featureChecker);
        $this->productPriceBuilder->addFeature('oro_price_lists_combined');
    }

    public function testBuildByPriceListNoRules()
    {
        $priceList = new PriceList();

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('oro_price_lists_combined')
            ->willReturn(true);

        $productId1 = 1;
        $productId2 = 2;
        $productId3 = 2;

        $repo = $this->getRepositoryMock();
        $repo->expects($this->exactly(2))
            ->method('deleteGeneratedPrices')
            ->withConsecutive(
                [$this->shardManager, $priceList, [$productId1, $productId2]],
                [$this->shardManager, $priceList, [$productId3]],
            );

        $this->insertFromSelectQueryExecutor->expects($this->never())
            ->method($this->anything());

        $this->priceListTriggerHandler->expects($this->exactly(2))
            ->method('handlePriceListTopic')
            ->withConsecutive(
                [ResolveCombinedPriceByPriceListTopic::getName(), $priceList, [$productId1, $productId2]],
                [ResolveCombinedPriceByPriceListTopic::getName(), $priceList, [$productId3]]
            );

        $this->productPriceBuilder->setBatchSize(2);
        $this->productPriceBuilder->buildByPriceList($priceList, [$productId1, $productId2, $productId3]);
    }

    public function testBuildByPriceListNoRulesWithAdjustedBatchSize()
    {
        $priceList = new PriceList();

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('oro_price_lists_combined')
            ->willReturn(true);

        $productId = 1;

        $repo = $this->getRepositoryMock();
        $repo->expects($this->once())
            ->method('deleteGeneratedPrices')
            ->with($this->shardManager, $priceList, [$productId]);

        $this->insertFromSelectQueryExecutor->expects($this->never())
            ->method($this->anything());

        $this->priceListTriggerHandler->expects($this->once())
            ->method('handlePriceListTopic')
            ->with(ResolveCombinedPriceByPriceListTopic::getName(), $priceList, [$productId]);

        $this->productPriceBuilder->buildByPriceList($priceList, [$productId]);
    }

    public function testBuildByPriceListNoRulesWithoutProduct()
    {
        $priceList = new PriceList();

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('oro_price_lists_combined')
            ->willReturn(true);

        $repo = $this->getRepositoryMock();
        $repo->expects($this->once())
            ->method('deleteGeneratedPrices')
            ->with($this->shardManager, $priceList, []);

        $this->insertFromSelectQueryExecutor->expects($this->never())
            ->method($this->anything());

        $this->priceListTriggerHandler->expects($this->once())
            ->method('handlePriceListTopic')
            ->with(ResolveCombinedPriceByPriceListTopic::getName(), $priceList, []);

        $this->productPriceBuilder->buildByPriceList($priceList);
    }

    public function testBuildByPriceList()
    {
        $priceList = new PriceList();

        $product = $this->createMock(Product::class);

        $rule1 = new PriceRule();
        $rule1->setPriority(10);
        $rule2 = new PriceRule();
        $rule2->setPriority(20);

        $priceList->setPriceRules(new ArrayCollection([$rule2, $rule1]));

        $fields = ['field1', 'field2'];

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('oro_price_lists_combined')
            ->willReturn(true);

        $repo = $this->getRepositoryMock();
        $repo->expects($this->once())
            ->method('deleteGeneratedPrices')
            ->with($this->shardManager, $priceList, [$product]);

        $qb = $this->assertInsertCall($fields, [$rule1, $rule2], [$product]);
        $this->insertFromSelectQueryExecutor->expects($this->exactly(2))
            ->method('execute')
            ->with(
                ProductPrice::class,
                $fields,
                $qb
            );

        $this->priceListTriggerHandler->expects($this->once())
            ->method('handlePriceListTopic')
            ->with(ResolveCombinedPriceByPriceListTopic::getName(), $priceList, [$product]);

        $this->productPriceBuilder->buildByPriceList($priceList, [$product]);
    }

    public function testBuildByPriceListFeatureDisabled()
    {
        $priceList = new PriceList();

        $product = $this->createMock(Product::class);

        $rule1 = new PriceRule();
        $rule1->setPriority(10);
        $rule2 = new PriceRule();
        $rule2->setPriority(20);

        $priceList->setPriceRules(new ArrayCollection([$rule2, $rule1]));

        $fields = ['field1', 'field2'];

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('oro_price_lists_combined')
            ->willReturn(false);

        $repo = $this->getRepositoryMock();
        $repo->expects($this->once())
            ->method('deleteGeneratedPrices')
            ->with($this->shardManager, $priceList, [$product]);

        $qb = $this->assertInsertCall($fields, [$rule1, $rule2], [$product]);
        $this->insertFromSelectQueryExecutor->expects($this->exactly(2))
            ->method('execute')
            ->with(
                ProductPrice::class,
                $fields,
                $qb
            );

        $this->priceListTriggerHandler->expects($this->never())
            ->method('handlePriceListTopic');

        $this->productPriceBuilder->buildByPriceList($priceList, [$product]);
    }

    public function testBuildByPriceListNoProductsProvided()
    {
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);

        $rule = new PriceRule();
        $rule->setPriority(10);

        $priceList->setPriceRules(new ArrayCollection([$rule]));

        $fields = ['field1', 'field2'];

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('oro_price_lists_combined')
            ->willReturn(true);

        $repo = $this->getRepositoryMock();
        $repo->expects($this->once())
            ->method('deleteGeneratedPrices')
            ->with($this->shardManager, $priceList, []);
        $repo->expects($this->once())
            ->method('getProductsByPriceListAndVersion')
            ->with($this->shardManager, $priceList->getId(), $this->isType('int'))
            ->willReturn([[1], [2]]);

        $qb = $this->assertInsertCall($fields, [$rule], []);
        $qb->expects($this->once())
            ->method('expr')
            ->willReturn(new Expr());
        $qb->expects($this->once())
            ->method('addSelect');

        $this->insertFromSelectQueryExecutor->expects($this->once())
            ->method('execute')
            ->with(
                ProductPrice::class,
                array_merge($fields, ['version']),
                $qb
            );

        $this->priceListTriggerHandler->expects($this->exactly(2))
            ->method('handlePriceListTopic')
            ->withConsecutive(
                [ResolveCombinedPriceByPriceListTopic::getName(), $priceList, [1]],
                [ResolveCombinedPriceByPriceListTopic::getName(), $priceList, [2]]
            );

        $this->productPriceBuilder->buildByPriceList($priceList, []);
    }

    public function testBuildByPriceListWithoutTriggers()
    {
        $rule = new PriceRule();
        $rule->setPriority(10);

        $priceList = new PriceList();
        $priceList->setPriceRules(new ArrayCollection([$rule]));

        $repository = $this->getRepositoryMock();
        $repository->expects($this->once())
            ->method('deleteGeneratedPrices')
            ->with($this->shardManager, $priceList, []);

        $this->featureChecker->expects($this->never())
            ->method('isFeatureEnabled');

        $fields = ['field1', 'field2'];
        $queryBuilder = $this->assertInsertCall($fields, [$rule]);
        $this->insertFromSelectQueryExecutor->expects($this->once())
            ->method('execute')
            ->with(
                ProductPrice::class,
                $fields,
                $queryBuilder
            );

        $this->priceListTriggerHandler->expects($this->never())
            ->method('handlePriceListTopic');

        $this->productPriceBuilder->buildByPriceListWithoutTriggers($priceList);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|ProductPriceRepository
     */
    private function getRepositoryMock()
    {
        $repo = $this->createMock(ProductPriceRepository::class);
        $em = $this->createMock(EntityManagerInterface::class);
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
     * @param int[]|Product[]|null $products
     * @return QueryBuilder|\PHPUnit\Framework\MockObject\MockObject
     */
    private function assertInsertCall(array $fields, array $rules, array $products = [])
    {
        $rulesCount = count($rules);

        $qb = $this->createMock(QueryBuilder::class);

        $this->ruleCompiler->expects($this->exactly($rulesCount))
            ->method('getOrderedFields')
            ->willReturn($fields);

        $this->ruleCompiler->expects($this->exactly($rulesCount))
            ->method('compile')
            ->willReturn($qb);

        $expectedRules = [];
        foreach ($rules as $rule) {
            $expectedRules[] = [$rule, $products];
        }

        $this->ruleCompiler->expects($this->exactly($rulesCount))
            ->method('compile')
            ->withConsecutive(...$expectedRules);

        return $qb;
    }
}
