<?php

namespace Oro\Bundle\PricingBundle\Builder;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\PricingBundle\Compiler\PriceListRuleCompiler;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\TriggersFiller\ScopeRecalculateTriggersFiller;
use Oro\Bundle\ProductBundle\Entity\Product;

class ProductPriceBuilder
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var InsertFromSelectQueryExecutor
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
     * @var ScopeRecalculateTriggersFiller
     */
    protected $triggersFiller;

    /**
     * @param ManagerRegistry $registry
     * @param InsertFromSelectQueryExecutor $insertFromSelectQueryExecutor
     * @param PriceListRuleCompiler $ruleCompiler
     * @param ScopeRecalculateTriggersFiller $triggersFiller
     */
    public function __construct(
        ManagerRegistry $registry,
        InsertFromSelectQueryExecutor $insertFromSelectQueryExecutor,
        PriceListRuleCompiler $ruleCompiler,
        ScopeRecalculateTriggersFiller $triggersFiller
    ) {
        $this->registry = $registry;
        $this->insertFromSelectQueryExecutor = $insertFromSelectQueryExecutor;
        $this->ruleCompiler = $ruleCompiler;
        $this->triggersFiller = $triggersFiller;
    }

    /**
     * @param PriceList $priceList
     * @param Product|null $product
     */
    public function buildByPriceList(PriceList $priceList, Product $product = null)
    {
        $this->getProductPriceRepository()->deleteGeneratedPrices($priceList, $product);
        if (count($priceList->getPriceRules()) > 0) {
            $rules = $this->getSortedRules($priceList);
            foreach ($rules as $rule) {
                $this->applyRule($rule, $product);
            }
        }
        $this->fillTriggers($priceList, $product);
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
     * @param Product|null $product
     */
    protected function fillTriggers(PriceList $priceList, Product $product = null)
    {
        if ($product === null) {
            $this->triggersFiller->fillTriggersByPriceList($priceList);
        } else {
            $this->triggersFiller->createTriggerByPriceListProduct($priceList, $product);
        }
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
