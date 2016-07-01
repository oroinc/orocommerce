<?php

namespace OroB2B\Bundle\PricingBundle\Compiler;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\Query\Expr\Select;
use Doctrine\ORM\QueryBuilder;

use OroB2B\Bundle\PricingBundle\Entity\PriceRule;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class PriceListRuleCompiler
{
    /**
     * @var Registry
     */
    protected $registry;

    protected $fieldsOrder = [
        'product',
        'priceList',
        'productUnit',
        'currency',
        'quantity',
        'productSku',
        'value',
    ];

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

        $this->modifySelectPart($qb, $rule, $calculationRule);

        if ($product) {
            $qb->where('product = :product')
                ->setParameter('product', $product);
        }

        $conditionalRule = "product.status = :status";
        $qb->andWhere($conditionalRule)
            ->setParameter('status', Product::STATUS_ENABLED);

        return $qb;
    }

    /**
     * @param QueryBuilder $qb
     * @param PriceRule $rule
     * @param mixed|string|Select $calculationRule
     */
    protected function modifySelectPart(QueryBuilder $qb, PriceRule $rule, $calculationRule)
    {
        $fieldsMap = [
            'product' => 'product.id',
            'productSku' => 'product.productSku',
            'priceList' => (string)$qb->expr()->literal($rule->getPriceList()->getId()),
            'productUnit' => (string)$qb->expr()->literal($rule->getProductUnit()->getCode()),
            'currency' => (string)$qb->expr()->literal($rule->getCurrency()),
            'quantity' => (string)$qb->expr()->literal($rule->getQuantity()),
            'value' => $calculationRule,
        ];

        foreach ($this->getFieldsOrder() as $fieldName) {
            $qb->addSelect($fieldsMap[$fieldName]);
        }
    }

    public function getFieldsOrder()
    {
        return $this->fieldsOrder;
    }
}
