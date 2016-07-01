<?php

namespace OroB2B\Bundle\PricingBundle\Compiler;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\QueryBuilder;

use OroB2B\Bundle\PricingBundle\Entity\PriceRule;
use OroB2B\Bundle\ProductBundle\Entity\Product;

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
     * @param Product $product
     * @return QueryBuilder
     */
    public function compileRule(PriceRule $rule, Product $product = null)
    {
        //TODO: build select query from PriceListToProduct
        //TODO: BB-3623
        $calculationRule = "2+2";

        $qb = $this->registry
            ->getManagerForClass('OroB2BProductBundle:Product')
            ->getRepository('OroB2BProductBundle:Product')
            ->createQueryBuilder('product');

        $qb->select((string)$qb->expr()->literal($rule->getPriceList()->getId()))
            ->addSelect((string)$qb->expr()->literal($rule->getProductUnit()->getCode()))
            ->addSelect((string)$qb->expr()->literal($rule->getCurrency()))
            ->addSelect((string)$qb->expr()->literal($rule->getQuantity()));

        $qb->addSelect($calculationRule);

        if ($product) {
            $qb->where('product = :product')
                ->setParameter('product', $product);
        }

        $conditionalRule = "product.status = :status";
        $qb->andWhere($conditionalRule)
            ->setParameter('status', Product::STATUS_ENABLED);

        return $qb;
    }
}
