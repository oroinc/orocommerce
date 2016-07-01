<?php

namespace OroB2B\Bundle\PricingBundle\Compiler;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\QueryBuilder;

use OroB2B\Bundle\PricingBundle\Entity\PriceRule;

class PriceListRuleCompiler
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @param Registry $registry
     */
    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param PriceRule $rule
     * @return QueryBuilder
     */
    public function compileRule(PriceRule $rule)
    {
        //TODO: build select query from PriceListToProduct
        //TODO: BB-3623
        $calculationRule = "2+2";
        $conditionalRule = "product.id = 1";

        $qb = $this->registry
            ->getManagerForClass('OroB2BProductBundle:Product')
            ->getRepository('OroB2BProductBundle:Product')
            ->createQueryBuilder('product');

        $qb->select((string)$qb->expr()->literal($rule->getPriceList()->getId()))
            ->addSelect((string)$qb->expr()->literal($rule->getProductUnit()->getCode()))
            ->addSelect((string)$qb->expr()->literal($rule->getCurrency()))
            ->addSelect((string)$qb->expr()->literal($rule->getQuantity()));

        $qb->addSelect($calculationRule);
        $qb->andWhere($conditionalRule);

        return $qb;
    }
}
