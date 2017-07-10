<?php

namespace Oro\Bundle\PromotionBundle\Provider;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\SegmentBundle\Entity\Manager\SegmentManager;
use Oro\Bundle\SegmentBundle\Entity\Segment;

/**
 * This provider returns products from line items which fit segment's conditions.
 */
class MatchingProductsProvider
{
    /**
     * @var SegmentManager
     */
    private $segmentManager;

    /**
     * @param SegmentManager $segmentManager
     */
    public function __construct(SegmentManager $segmentManager)
    {
        $this->segmentManager = $segmentManager;
    }

    /**
     * @param Segment $segment
     * @param array|DiscountLineItem[] $lineItems
     * @return bool
     *
     * @throws \RuntimeException
     */
    public function hasMatchingProducts(Segment $segment, array $lineItems) : bool
    {
        if (empty($lineItems)) {
            return false;
        }

        $queryBuilder = $this->modifyQueryBuilder($segment, $lineItems);

        $queryBuilder->setMaxResults(1);
        $queryBuilder->select('1');

        return !empty($queryBuilder->getQuery()->getArrayResult());
    }

    /**
     * @param Segment $segment
     * @param array|DiscountLineItem[] $lineItems
     * @return array|Product[]
     *
     * @throws \RuntimeException
     */
    public function getMatchingProducts(Segment $segment, array $lineItems) : array
    {
        if (empty($lineItems)) {
            return [];
        }

        $queryBuilder = $this->modifyQueryBuilder($segment, $lineItems);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param Segment $segment
     * @param array|DiscountLineItem[] $lineItems
     * @return QueryBuilder
     */
    private function modifyQueryBuilder(Segment $segment, array $lineItems) : QueryBuilder
    {
        $queryBuilder = $this->segmentManager->getEntityQueryBuilder($segment);

        if (!$queryBuilder) {
            throw new \RuntimeException('Cannot get query builder for segment');
        }

        $products = [];
        /** @var DiscountLineItem $lineItem */
        foreach ($lineItems as $lineItem) {
            $products[] = $lineItem->getProduct();
        }

        $rootAliases = $queryBuilder->getRootAliases();
        if (empty($rootAliases)) {
            throw new \RuntimeException('No root alias for segment\'s query builder');
        }

        $rootAlias = reset($rootAliases);

        $queryBuilder
            ->andWhere($queryBuilder->expr()->in($rootAlias, ':products'))
            ->setParameter('products', $products);

        return $queryBuilder;
    }
}
