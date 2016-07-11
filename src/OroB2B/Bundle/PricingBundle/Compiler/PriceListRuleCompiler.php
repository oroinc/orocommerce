<?php

namespace OroB2B\Bundle\PricingBundle\Compiler;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

use OroB2B\Bundle\PricingBundle\Entity\PriceRule;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class PriceListRuleCompiler extends AbstractRuleCompiler
{
    /**
     * @var array
     */
    protected $fieldsOrder = [
        'product',
        'priceList',
        'unit',
        'currency',
        'quantity',
        'productSku',
        'priceRule',
        'value'
    ];

    /**
     * @param PriceRule $rule
     * @param Product $product
     * @return QueryBuilder
     */
    public function compile(PriceRule $rule, Product $product = null)
    {
        $qb = $this->createQueryBuilder($rule);
        $aliases = $qb->getRootAliases();
        $rootAlias = reset($aliases);

        $this->modifySelectPart($qb, $rule, $rootAlias);
        $this->applyRuleConditions($qb, $rule);
        $this->restrictByAssignedProducts($rule, $qb, $rootAlias);
        $this->restrictByManualPrices($qb, $rule, $rootAlias);
        $this->restrictByGivenProduct($qb, $rootAlias, $product);

        return $qb;
    }

    /**
     * @param PriceRule $rule
     * @return QueryBuilder
     */
    protected function createQueryBuilder(PriceRule $rule)
    {
        $expression = sprintf('%s and (%s) > 0', $rule->getRuleCondition(), $rule->getRule());
        $node = $this->expressionParser->parse($expression);
        $source = $this->nodeConverter->convert($node);

        return $this->queryConverter->convert($source);
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldsOrder()
    {
        return $this->fieldsOrder;
    }

    /**
     * @param QueryBuilder $qb
     * @param PriceRule $rule
     * @param string $rootAlias
     */
    protected function modifySelectPart(QueryBuilder $qb, PriceRule $rule, $rootAlias)
    {
        $params = [];
        $this->addSelectInOrder(
            $qb,
            [
                'product' => $rootAlias . '.id',
                'productSku' => $rootAlias . '.sku',
                'priceList' => (string)$qb->expr()->literal($rule->getPriceList()->getId()),
                'unit' => (string)$qb->expr()->literal($rule->getProductUnit()->getCode()),
                'currency' => (string)$qb->expr()->literal($rule->getCurrency()),
                'quantity' => (string)$qb->expr()->literal($rule->getQuantity()),
                'priceRule' => (string)$qb->expr()->literal($rule->getId()),
                'value' => (string)$this->expressionBuilder->convert(
                    $this->expressionParser->parse($rule->getRule()),
                    $qb->expr(),
                    $params,
                    $this->queryConverter->getTableAliasByColumn()
                )
            ]
        );
        $this->applyParameters($qb, $params);
    }

    /**
     * @param QueryBuilder $qb
     * @param PriceRule $rule
     */
    protected function applyRuleConditions(QueryBuilder $qb, PriceRule $rule)
    {
        $params = [];
        $qb->andWhere(
            $this->expressionBuilder->convert(
                $this->expressionParser->parse($rule->getRuleCondition()),
                $qb->expr(),
                $params,
                $this->queryConverter->getTableAliasByColumn()
            )
        );
        $this->applyParameters($qb, $params);
    }

    /**
     * Manually entered prices should not be rewritten by generator.
     *
     * @param QueryBuilder $qb
     * @param PriceRule $rule
     * @param string $rootAlias
     */
    protected function restrictByManualPrices(QueryBuilder $qb, PriceRule $rule, $rootAlias)
    {
        /** @var EntityManagerInterface $em */
        $em = $qb->getEntityManager();
        $subQb = $em->createQueryBuilder();
        $subQb->from('OroB2BPricingBundle:ProductPrice', 'productPriceOld')
            ->select('productPriceOld')
            ->where(
                $subQb->expr()->andX(
                    $subQb->expr()->eq('productPriceOld.product', $rootAlias),
                    $subQb->expr()->eq('productPriceOld.priceList', ':priceListOld'),
                    $subQb->expr()->eq('productPriceOld.unit', ':unitOld'),
                    $subQb->expr()->eq('productPriceOld.currency', ':currencyOld'),
                    $subQb->expr()->eq('productPriceOld.quantity', ':quantityOld'),
                    $subQb->expr()->isNull('productPriceOld.priceRule')
                )
            );

        $qb->setParameter('priceListOld', $rule->getPriceList()->getId())
            ->setParameter('unitOld', $rule->getProductUnit()->getCode())
            ->setParameter('currencyOld', $rule->getCurrency())
            ->setParameter('quantityOld', $rule->getQuantity())
            ->andWhere(
                $qb->expr()->not(
                    $qb->expr()->exists(
                        $subQb->getQuery()->getDQL()
                    )
                )
            );
    }

    /**
     * @param PriceRule $rule
     * @param QueryBuilder $qb
     * @param string $rootAlias
     */
    protected function restrictByAssignedProducts(PriceRule $rule, QueryBuilder $qb, $rootAlias)
    {
        $qb
            ->join(
                'OroB2BPricingBundle:PriceListToProduct',
                'priceListToProduct',
                Join::WITH,
                $qb->expr()->eq('priceListToProduct.product', $rootAlias)
            )
            ->andWhere($qb->expr()->eq('priceListToProduct.priceList', ':priceList'))
            ->setParameter('priceList', $rule->getPriceList());
    }

    /**
     * @param QueryBuilder $qb
     * @param string $rootAlias
     * @param Product $product
     */
    protected function restrictByGivenProduct(QueryBuilder $qb, $rootAlias, Product $product = null)
    {
        if ($product) {
            $qb->andWhere($qb->expr()->eq($rootAlias, ':product'))
                ->setParameter('product', $product);
        }
    }
}
