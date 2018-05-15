<?php

namespace Oro\Bundle\PricingBundle\Builder;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Compiler\PriceListRuleCompiler;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerHandler;
use Oro\Bundle\PricingBundle\ORM\ShardQueryExecutorInterface;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\ProductBundle\Entity\Product;

class ProductPriceBuilder
{
    /**
     * @var ShardManager
     */
    protected $shardManager;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var ShardQueryExecutorInterface
     */
    protected $insertFromSelectQueryExecutor;

    /**
     * @var PriceListRuleCompiler
     */
    protected $ruleCompiler;

    /**
     * @var ProductPriceRepository
     */
    protected $productPriceRepository;

    /**
     * @var PriceListTriggerHandler
     */
    protected $priceListTriggerHandler;

    /**
     * @param ManagerRegistry $registry
     * @param ShardQueryExecutorInterface $insertFromSelectQueryExecutor
     * @param PriceListRuleCompiler $ruleCompiler
     * @param PriceListTriggerHandler $priceListTriggerHandler
     * @param ShardManager $shardManager
     */
    public function __construct(
        ManagerRegistry $registry,
        ShardQueryExecutorInterface $insertFromSelectQueryExecutor,
        PriceListRuleCompiler $ruleCompiler,
        PriceListTriggerHandler $priceListTriggerHandler,
        ShardManager $shardManager
    ) {
        $this->registry = $registry;
        $this->insertFromSelectQueryExecutor = $insertFromSelectQueryExecutor;
        $this->ruleCompiler = $ruleCompiler;
        $this->priceListTriggerHandler = $priceListTriggerHandler;
        $this->shardManager = $shardManager;
    }

    /**
     * @param PriceList $priceList
     * @param array|Product[] $products
     */
    public function buildByPriceList(PriceList $priceList, array $products = [])
    {
        $this->buildByPriceListWithoutTriggerSend($priceList, $products);
        $this->flush();
    }

    /**
     * @param PriceList $priceList
     * @param array|Product[] $products
     */
    public function buildByPriceListWithoutTriggerSend(PriceList $priceList, array $products = [])
    {
        $this->buildByPriceListWithoutTriggers($priceList, $products);

        $this->priceListTriggerHandler->addTriggerForPriceList(Topics::RESOLVE_COMBINED_PRICES, $priceList, $products);
    }

    public function flush()
    {
        $this->priceListTriggerHandler->sendScheduledTriggers();
    }

    /**
     * @param PriceRule $priceRule
     * @param array|Product[] $products
     */
    protected function applyRule(PriceRule $priceRule, array $products = [])
    {
        $this->insertFromSelectQueryExecutor->execute(
            ProductPrice::class,
            $this->ruleCompiler->getOrderedFields(),
            $this->ruleCompiler->compile($priceRule, $products)
        );
    }

    /**
     * @return ProductPriceRepository
     */
    protected function getProductPriceRepository()
    {
        if (!$this->productPriceRepository) {
            $this->productPriceRepository = $this->registry
                ->getManagerForClass(ProductPrice::class)
                ->getRepository(ProductPrice::class);
        }

        return $this->productPriceRepository;
    }

    /**
     * @param PriceList $priceList
     * @return array
     */
    protected function getSortedRules(PriceList $priceList)
    {
        $rules = $priceList->getPriceRules()->toArray();
        usort(
            $rules,
            function (PriceRule $a, PriceRule $b) {
                if ($a->getPriority() === $b->getPriority()) {
                    return 0;
                }

                return $a->getPriority() < $b->getPriority() ? -1 : 1;
            }
        );

        return $rules;
    }

    /**
     * @param PriceList $priceList
     * @param array|Product[] $products
     */
    public function buildByPriceListWithoutTriggers(PriceList $priceList, array $products = [])
    {
        $this->getProductPriceRepository()->deleteGeneratedPrices($this->shardManager, $priceList, $products);
        if (count($priceList->getPriceRules()) > 0) {
            $rules = $this->getSortedRules($priceList);
            foreach ($rules as $rule) {
                $this->applyRule($rule, $products);
            }
        }
    }
}
