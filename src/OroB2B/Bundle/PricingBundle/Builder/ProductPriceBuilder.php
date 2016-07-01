<?php

namespace OroB2B\Bundle\PricingBundle\Builder;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;

use OroB2B\Bundle\PricingBundle\Compiler\PriceListRuleCompiler;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceRule;
use OroB2B\Bundle\PricingBundle\RecalculateTriggersFiller\ScopeRecalculateTriggersFiller;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class ProductPriceBuilder
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var PriceListRuleCompiler
     */
    protected $ruleCompiler;

    /**
     * @var InsertFromSelectQueryExecutor
     */
    protected $queryHelper;

    /**
     * @var ScopeRecalculateTriggersFiller
     */
    protected $triggersFiller;

    /**
     * @param Registry $registry
     * @param PriceListRuleCompiler $ruleCompiler
     * @param InsertFromSelectQueryExecutor $queryHelper
     * @param ScopeRecalculateTriggersFiller $triggersFiller
     */
    public function __construct(
        Registry $registry,
        PriceListRuleCompiler $ruleCompiler,
        InsertFromSelectQueryExecutor $queryHelper,
        ScopeRecalculateTriggersFiller $triggersFiller
    ) {
        $this->registry = $registry;
        $this->ruleCompiler = $ruleCompiler;
        $this->queryHelper = $queryHelper;
        $this->triggersFiller = $triggersFiller;
    }

    public function buildByPriceList(PriceList $priceList)
    {
        foreach ($priceList->getPriceRules() as $rule) {
            $this->applyRule($rule);
        }
        $this->triggersFiller->fillTriggersByPriceList($priceList);
    }

    public function buildByRule(PriceRule $rule, Product $product = null)
    {
        $this->applyRule($rule, $product);
        //TODO: create ProductPriceChangeTrigger, may be modify $triggersFiller for this
        $this->triggersFiller->fillTriggersByPriceList($rule->getPriceList());
    }

    protected function applyRule(PriceRule $rule, Product $product = null)
    {
        //TODO: clear old prices
        //TODO: handle duplicated prices
        $qb = $this->ruleCompiler->compileRule($rule, $product);
        $this->queryHelper->execute(
            'OroB2BPricingBundle:ProductPrice',
            $this->ruleCompiler->getFieldsOrder(),
            $qb
        );
    }
}
