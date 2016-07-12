<?php

namespace OroB2B\Bundle\PricingBundle\Expression\Executor;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;

use OroB2B\Bundle\PricingBundle\Compiler\ProductAssignmentRuleCompiler;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToProduct;

class ProductAssignmentRuleExecutor
{
    /**
     * @var InsertFromSelectQueryExecutor
     */
    protected $insertFromSelectQueryExecutor;

    /**
     * @var ProductAssignmentRuleCompiler
     */
    protected $ruleCompiler;

    /**
     * @param InsertFromSelectQueryExecutor $insertFromSelectQueryExecutor
     * @param ProductAssignmentRuleCompiler $ruleCompiler
     */
    public function __construct(
        InsertFromSelectQueryExecutor $insertFromSelectQueryExecutor,
        ProductAssignmentRuleCompiler $ruleCompiler
    ) {
        $this->insertFromSelectQueryExecutor = $insertFromSelectQueryExecutor;
        $this->ruleCompiler = $ruleCompiler;
    }

    /**
     * @param PriceList $priceList
     */
    public function execute(PriceList $priceList)
    {
        if ($priceList->getProductAssignmentRule()) {
            $this->insertFromSelectQueryExecutor->execute(
                PriceListToProduct::class,
                $this->ruleCompiler->getOrderedFields(),
                $this->ruleCompiler->compile($priceList)
            );
        }
    }
}
