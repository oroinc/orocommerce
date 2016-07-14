<?php

namespace OroB2B\Bundle\PricingBundle\Builder;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;

use OroB2B\Bundle\PricingBundle\Compiler\PriceListRuleCompiler;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceRule;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use OroB2B\Bundle\PricingBundle\TriggersFiller\ScopeRecalculateTriggersFiller;
use OroB2B\Bundle\ProductBundle\Entity\Product;

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
     * @param Product $product
     */
    public function buildByPriceList(PriceList $priceList, Product $product = null)
    {
        $this->getProductPriceRepository()->deleteGeneratedPrices($priceList, $product);
        if (count($priceList->getPriceRules()) > 0) {
            $rules = $this->getSortedRules($priceList);
            foreach ($rules as $rule) {
                $this->buildByRule($rule, $product, true);
            }
        }
    }

    /**
     * @param PriceRule $priceRule
     * @param Product|null $product
     * @param bool $skipClear
     */
    public function buildByRule(PriceRule $priceRule, Product $product = null, $skipClear = false)
    {
        $this->applyRule($priceRule, $product, $skipClear);
        if ($product === null) {
            $this->triggersFiller->fillTriggersByPriceList($priceRule->getPriceList());
        } else {
            $this->triggersFiller->createTriggerByPriceListProduct($priceRule->getPriceList(), $product);
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

    /**
     * @param PriceRule $priceRule
     * @param Product|null $product
     * @param bool $skipClear
     */
    protected function applyRule(PriceRule $priceRule, Product $product = null, $skipClear = false)
    {
        if (!$skipClear) {
            $this->getProductPriceRepository()->deleteGeneratedPricesByRule($priceRule, $product);
        }
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
}
