<?php

namespace Oro\Bundle\PricingBundle\Compiler;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Compile product assignment rule to Query builder with all applied restrictions.
 */
class ProductAssignmentRuleCompiler extends AbstractRuleCompiler
{
    /**
     * @var array
     */
    protected $fieldsOrder = [
        'id',
        'product',
        'priceList',
        'manual'
    ];

    /**
     * @param PriceList $priceList
     * @param array|Product[] $products
     * @return QueryBuilder|null
     */
    public function compile(PriceList $priceList, array $products = [])
    {
        if (!$priceList->getId()) {
            throw new \InvalidArgumentException(
                sprintf('Cannot compile product assignment rule: %s was expected to have id', PriceList::class)
            );
        }

        if (!$priceList->getProductAssignmentRule()) {
            return null;
        }

        $cacheKey = 'ar_' . $priceList->getId();
        $qb = $this->cache->fetch($cacheKey);
        if (!$qb) {
            $qb = $this->compileQueryBuilder($priceList);

            $this->cache->save($cacheKey, $qb);
        }
        $this->restrictByGivenProduct($qb, $products);

        return $qb;
    }

    public function compileQueryBuilder(PriceList $priceList): QueryBuilder
    {
        $qb = $this->createQueryBuilder($priceList);
        $aliases = $qb->getRootAliases();
        $rootAlias = reset($aliases);

        $this->modifySelectPart($qb, $priceList, $rootAlias);
        $this->applyRuleConditions($qb, $priceList);
        $this->restrictByManualPrices($qb, $priceList, $rootAlias);
        $qb->addGroupBy($rootAlias . '.id');

        return $qb;
    }

    /**
     * @param PriceList $priceList
     * @return QueryBuilder
     */
    protected function createQueryBuilder(PriceList $priceList)
    {
        $rule = $this->getProcessedAssignmentRule($priceList);
        $node = $this->expressionParser->parse($rule);
        $source = $this->nodeConverter->convert($node);

        return $this->queryConverter->convert($source);
    }

    /**
     * {@inheritdoc}
     */
    public function getOrderedFields()
    {
        return $this->fieldsOrder;
    }

    /**
     * @param QueryBuilder $qb
     * @param PriceList $priceList
     * @param string $rootAlias
     */
    protected function modifySelectPart(QueryBuilder $qb, PriceList $priceList, $rootAlias)
    {
        $this->addSelectInOrder(
            $qb,
            [
                'id' => 'UUID()',
                'product' => $rootAlias . '.id',
                'priceList' => (string)$qb->expr()->literal((int)$priceList->getId()),
                'manual' => 'CAST(0 as boolean)'
            ]
        );
    }

    protected function applyRuleConditions(QueryBuilder $qb, PriceList $priceList)
    {
        $params = [];
        $rule = $this->getProcessedAssignmentRule($priceList);
        $qb->andWhere(
            $this->expressionBuilder->convert(
                $this->expressionParser->parse($rule),
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
     * @param PriceList $priceList
     * @param string $rootAlias
     */
    protected function restrictByManualPrices(QueryBuilder $qb, PriceList $priceList, $rootAlias)
    {
        /** @var EntityManagerInterface $em */
        $em = $qb->getEntityManager();
        $subQb = $em->createQueryBuilder();
        $subQb->from('OroPricingBundle:PriceListToProduct', 'PriceListToProductOld')
            ->select('PriceListToProductOld')
            ->where(
                $subQb->expr()->andX(
                    $subQb->expr()->eq('PriceListToProductOld.product', $rootAlias),
                    $subQb->expr()->eq('PriceListToProductOld.priceList', ':priceList'),
                    $subQb->expr()->eq('PriceListToProductOld.manual', ':isManual')
                )
            );

        $qb->setParameter('isManual', true)
            ->setParameter('priceList', $priceList->getId())
            ->andWhere(
                $qb->expr()->not(
                    $qb->expr()->exists(
                        $subQb->getQuery()->getDQL()
                    )
                )
            );
    }

    /**
     * @param QueryBuilder $qb
     * @param array|Product[] $products
     */
    protected function restrictByGivenProduct(QueryBuilder $qb, array $products = [])
    {
        if ($products) {
            $aliases = $qb->getRootAliases();
            $rootAlias = reset($aliases);
            $qb->andWhere($qb->expr()->in($rootAlias, ':products'))
                ->setParameter('products', $products);
        }
    }

    /**
     * @param PriceList $priceList
     * @return string
     */
    protected function getProcessedAssignmentRule(PriceList $priceList)
    {
        return $this->expressionPreprocessor->process($priceList->getProductAssignmentRule());
    }
}
