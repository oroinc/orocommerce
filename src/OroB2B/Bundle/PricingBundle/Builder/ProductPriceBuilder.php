<?php

namespace OroB2B\Bundle\PricingBundle\Builder;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;

use OroB2B\Bundle\PricingBundle\Compiler\PriceListRuleCompiler;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceRule;
use OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
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
     * @var ProductPriceRepository
     */
    protected $productPriceRepository;

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

    /**
     * @param PriceList $priceList
     */
    public function buildByPriceList(PriceList $priceList)
    {
        $this->getProductPriceRepository()->deleteGeneratedPrices($priceList);
        foreach ($priceList->getPriceRules() as $rule) {
            $this->applyRule($rule);
        }
        $this->triggersFiller->fillTriggersByPriceList($priceList);
    }

    /**
     * @param PriceRule $rule
     * @param Product|null $product
     */
    public function buildByRule(PriceRule $rule, Product $product = null)
    {
        $this->applyRule($rule, $product);
        //TODO: create ProductPriceChangeTrigger, maybe modify $triggersFiller for this
        $this->triggersFiller->fillTriggersByPriceList($rule->getPriceList());
    }

    /**
     * @param PriceRule $rule
     * @param Product|null $product
     */
    protected function applyRule(PriceRule $rule, Product $product = null)
    {
        $qb = $this->ruleCompiler->compileRule($rule, $product);
        $this->queryHelper->execute(
            'OroB2BPricingBundle:ProductPrice',
            $this->ruleCompiler->getFieldsOrder(),
            $qb
        );
    }

    /**
     * @return ProductPriceRepository
     */
    protected function getProductPriceRepository()
    {
        if ($this->productPriceRepository === null) {
            $this->productPriceRepository = $this->registry->getRepository('OroB2BPricingBundle:ProductPrice');
        }

        return $this->productPriceRepository;
    }
}
