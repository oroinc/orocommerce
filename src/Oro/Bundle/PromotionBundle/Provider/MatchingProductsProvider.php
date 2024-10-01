<?php

namespace Oro\Bundle\PromotionBundle\Provider;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\SegmentBundle\Entity\Manager\SegmentManager;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * This provider returns products from line items which fit segment's conditions.
 */
class MatchingProductsProvider implements MatchingProductsProviderInterface
{
    private SegmentManager $segmentManager;
    private CacheInterface $matchingProductsCache;

    public function __construct(SegmentManager $segmentManager, CacheInterface $matchingProductsCache)
    {
        $this->segmentManager = $segmentManager;
        $this->matchingProductsCache = $matchingProductsCache;
    }

    #[\Override]
    public function hasMatchingProducts(Segment $segment, array $lineItems): bool
    {
        if (empty($lineItems)) {
            return false;
        }

        $queryBuilder = $this->modifyQueryBuilder($segment, $lineItems);

        $queryBuilder->setMaxResults(1);
        $queryBuilder->select('1');

        return !empty($queryBuilder->getQuery()->getArrayResult());
    }

    #[\Override]
    public function getMatchingProducts(
        Segment $segment,
        array $lineItems,
        ?Organization $promotionOrganization = null
    ): array {
        if (empty($lineItems)) {
            return [];
        }

        $cacheKey = $this->getCacheKey($segment, $lineItems, $promotionOrganization);
        return $this->matchingProductsCache->get(
            $cacheKey,
            function () use ($segment, $lineItems, $promotionOrganization) {
                $queryBuilder = $this->modifyQueryBuilder($segment, $lineItems, $promotionOrganization);
                return $queryBuilder->getQuery()->getResult();
            }
        );
    }

    private function modifyQueryBuilder(
        Segment $segment,
        array $lineItems,
        ?Organization $promotionOrganization = null
    ): QueryBuilder {
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

        if ($promotionOrganization) {
            $queryBuilder
                ->andWhere($queryBuilder->expr()->eq($rootAlias . '.organization', ':organization'))
                ->setParameter('organization', $promotionOrganization);
        }
        return $queryBuilder;
    }

    private function getCacheKey(
        Segment $segment,
        array $discountLineItems,
        ?Organization $promotionOrganization = null
    ): string {
        $lineItemsProductIds = array_map(
            function (DiscountLineItem $discountLineItem) {
                return $discountLineItem->getProduct()->getId();
            },
            $discountLineItems
        );

        sort($lineItemsProductIds);

        $orgId = $promotionOrganization ? $promotionOrganization->getId() : null;

        return md5($segment->getDefinition() . '_' . implode(',', $lineItemsProductIds) . '_' . $orgId);
    }
}
