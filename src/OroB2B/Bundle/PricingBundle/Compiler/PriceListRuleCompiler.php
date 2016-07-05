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
        'unit',
        'currency',
        'quantity',
        'productSku',
        'value',
        'priceRule'
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
        //TODO: build select query from PriceListToProduct from BB-3665
        //TODO: BB-3623

        $qb = $this->registry
            ->getManagerForClass('OroB2BProductBundle:Product')
            ->getRepository('OroB2BProductBundle:Product')
            ->createQueryBuilder('product');

        $this->modifySelectPart($qb, $rule);

        if ($product) {
            $qb->where('product = :product')
                ->setParameter('product', $product);
        }

        $qb->andWhere($rule->getRuleCondition());

        $this->restrictByExistPrices($qb, $rule);

        return $qb;
    }

    public function getFieldsOrder()
    {
        return $this->fieldsOrder;
    }

    /**
     * @param QueryBuilder $qb
     * @param PriceRule $rule
     */
    protected function modifySelectPart(QueryBuilder $qb, PriceRule $rule)
    {
        $fieldsMap = [
            'product' => 'product.id',
            'productSku' => 'product.sku',
            'priceList' => (string)$qb->expr()->literal($rule->getPriceList()->getId()),
            'unit' => (string)$qb->expr()->literal($rule->getProductUnit()->getCode()),
            'currency' => (string)$qb->expr()->literal($rule->getCurrency()),
            'quantity' => (string)$qb->expr()->literal($rule->getQuantity()),
            'value' => $rule->getRule(),
            'priceRule' => (string)$qb->expr()->literal($rule->getId())
        ];
        $select = [];
        foreach ($this->getFieldsOrder() as $fieldName) {
            $select[] = $fieldsMap[$fieldName];
        }
        $qb->select($select);
    }

    /**
     * @param QueryBuilder $qb
     * @param PriceRule $rule
     */
    protected function restrictByExistPrices(QueryBuilder $qb, PriceRule $rule)
    {
        $subQb = $this->registry
            ->getManagerForClass('OroB2BPricingBundle:ProductPrice')
            ->getRepository('OroB2BPricingBundle:ProductPrice')
            ->createQueryBuilder('productPriceOld');
        $subQb->andWhere($subQb->expr()->eq('productPriceOld.priceList', ':priceListOld'))
            ->andWhere($subQb->expr()->eq('productPriceOld.product', 'product'))
            ->andWhere($subQb->expr()->eq('productPriceOld.unit', ':unitOld'))
            ->andWhere($subQb->expr()->eq('productPriceOld.currency', ':currencyOld'))
            ->andWhere($subQb->expr()->eq('productPriceOld.quantity', ':quantityOld'));

        $qb->setParameter('priceListOld', $rule->getPriceList()->getId())
            ->setParameter('unitOld', $rule->getProductUnit()->getCode())
            ->setParameter('currencyOld', $rule->getCurrency())
            ->setParameter('quantityOld', $rule->getQuantity());
        $qb->andWhere(
            $qb->expr()->not($qb->expr()->exists($subQb->getQuery()->getDQL()))
        );
    }
}
