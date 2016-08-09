<?php

namespace OroB2B\Bundle\PricingBundle\Builder;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;

use OroB2B\Bundle\PricingBundle\Compiler\ProductAssignmentRuleCompiler;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToProduct;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;

class PriceListProductAssignmentBuilder
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
     * @var ProductAssignmentRuleCompiler
     */
    protected $ruleCompiler;

    /**
     * @param ManagerRegistry $registry
     * @param InsertFromSelectQueryExecutor $insertFromSelectQueryExecutor
     * @param ProductAssignmentRuleCompiler $ruleCompiler
     */
    public function __construct(
        ManagerRegistry $registry,
        InsertFromSelectQueryExecutor $insertFromSelectQueryExecutor,
        ProductAssignmentRuleCompiler $ruleCompiler
    ) {
        $this->registry = $registry;
        $this->insertFromSelectQueryExecutor = $insertFromSelectQueryExecutor;
        $this->ruleCompiler = $ruleCompiler;
    }

    /**
     * @param PriceList $priceList
     */
    public function buildByPriceList(PriceList $priceList)
    {
        $this->clearGenerated($priceList);
        if ($priceList->getProductAssignmentRule()) {
            $this->insertFromSelectQueryExecutor->execute(
                PriceListToProduct::class,
                $this->ruleCompiler->getOrderedFields(),
                $this->ruleCompiler->compile($priceList)
            );
        }
        $this->registry->getManagerForClass(ProductPrice::class)
            ->getRepository(ProductPrice::class)
            ->deleteInvalidPrices($priceList);
    }

    /**
     * @param PriceList $priceList
     */
    protected function clearGenerated(PriceList $priceList)
    {
        $this->registry->getManagerForClass(PriceListToProduct::class)
            ->getRepository(PriceListToProduct::class)
            ->deleteGeneratedRelations($priceList);
    }
}
