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
use Oro\Bundle\PricingBundle\ORM\InsertFromSelectShardQueryExecutor;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\DoctrineUtils\ORM\QueryHintResolverInterface;

class ProductPriceBuilder
{
    /**
     * @var QueryHintResolverInterface
     */
    protected $hintResolver;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var InsertFromSelectShardQueryExecutor
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
     * @param InsertFromSelectShardQueryExecutor $insertFromSelectQueryExecutor
     * @param PriceListRuleCompiler $ruleCompiler
     * @param PriceListTriggerHandler $priceListTriggerHandler
     * @param QueryHintResolverInterface $hintResolver
     */
    public function __construct(
        ManagerRegistry $registry,
        InsertFromSelectShardQueryExecutor $insertFromSelectQueryExecutor,
        PriceListRuleCompiler $ruleCompiler,
        PriceListTriggerHandler $priceListTriggerHandler,
        QueryHintResolverInterface $hintResolver
    ) {
        $this->registry = $registry;
        $this->insertFromSelectQueryExecutor = $insertFromSelectQueryExecutor;
        $this->ruleCompiler = $ruleCompiler;
        $this->priceListTriggerHandler = $priceListTriggerHandler;
        $this->hintResolver = $hintResolver;
    }

    /**
     * @param PriceList $priceList
     * @param Product|null $product
     */
    public function buildByPriceList(PriceList $priceList, Product $product = null)
    {
        $this->getProductPriceRepository()->deleteGeneratedPrices($this->hintResolver, $priceList, $product);
        if (count($priceList->getPriceRules()) > 0) {
            $rules = $this->getSortedRules($priceList);
            foreach ($rules as $rule) {
                $this->applyRule($rule, $product);
            }
        }
        $this->priceListTriggerHandler->addTriggerForPriceList(Topics::RESOLVE_COMBINED_PRICES, $priceList, $product);
        $this->priceListTriggerHandler->sendScheduledTriggers();
    }

    /**
     * @param PriceRule $priceRule
     * @param Product|null $product
     */
    protected function applyRule(PriceRule $priceRule, Product $product = null)
    {
        $this->insertFromSelectQueryExecutor->execute(
            ProductPrice::class,
            $this->ruleCompiler->getOrderedFields(),
            $this->ruleCompiler->compile($priceRule, $product)
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
}
