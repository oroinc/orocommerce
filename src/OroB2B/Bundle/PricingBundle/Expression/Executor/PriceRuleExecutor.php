<?php

namespace OroB2B\Bundle\PricingBundle\Expression\Executor;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;

use OroB2B\Bundle\PricingBundle\Compiler\PriceListRuleCompiler;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceRule;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class PriceRuleExecutor
{
    /**
     * @var InsertFromSelectQueryExecutor
     */
    protected $insertFromSelectQueryExecutor;

    /**
     * @var PriceListRuleCompiler
     */
    protected $ruleCompiler;

    /**
     * @param InsertFromSelectQueryExecutor $insertFromSelectQueryExecutor
     * @param PriceListRuleCompiler $ruleCompiler
     */
    public function __construct(
        InsertFromSelectQueryExecutor $insertFromSelectQueryExecutor,
        PriceListRuleCompiler $ruleCompiler
    ) {
        $this->insertFromSelectQueryExecutor = $insertFromSelectQueryExecutor;
        $this->ruleCompiler = $ruleCompiler;
    }

    /**
     * @param PriceList $priceList
     * @param Product|null $product
     */
    public function executeAll(PriceList $priceList, Product $product = null)
    {
        if (count($priceList->getPriceRules()) > 0) {
            $rules = $this->getSortedRules($priceList);
            foreach ($rules as $rule) {
                $this->execute($rule, $product);
            }
        }
    }

    /**
     * @param PriceRule $priceRule
     * @param Product|null $product
     */
    public function execute(PriceRule $priceRule, Product $product = null)
    {
        $this->insertFromSelectQueryExecutor->execute(
            ProductPrice::class,
            $this->ruleCompiler->getOrderedFields(),
            $this->ruleCompiler->compile($priceRule, $product)
        );
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
