<?php

namespace Oro\Bundle\PromotionBundle\Provider;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\SegmentBundle\Entity\Manager\SegmentManager;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
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

    #[\Override]
    public function getMatchingProductIds(
        Segment $segment,
        array $lineItems,
        ?Organization $promotionOrganization = null
    ): array {
        if (empty($lineItems)) {
            return [];
        }

        // Use a dedicated cache key so the product ids are not mixed up with the Product entities
        // cached by getMatchingProducts() for the same segment, line items and organization.
        $cacheKey = $this->getCacheKey($segment, $lineItems, $promotionOrganization) . '_ids';
        return $this->matchingProductsCache->get(
            $cacheKey,
            function () use ($segment, $lineItems, $promotionOrganization) {
                $queryBuilder = $this->modifyQueryBuilder($segment, $lineItems, $promotionOrganization);
                $rootAlias = $this->getRootAlias($queryBuilder);
                $queryBuilder->select(QueryBuilderUtil::sprintf('%s.id', $rootAlias));

                return array_column($queryBuilder->getQuery()->getArrayResult(), 'id');
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

        $productIds = [];
        /** @var DiscountLineItem $lineItem */
        foreach ($lineItems as $lineItem) {
            $lineItemProduct = $lineItem->getProduct();
            if (!$lineItemProduct) {
                continue;
            }

            $productId = $lineItemProduct->getId();
            $productIds[$productId] = $productId;
        }

        $rootAlias = $this->getRootAlias($queryBuilder);

        $queryBuilder
            ->andWhere($queryBuilder->expr()->in(QueryBuilderUtil::sprintf('%s.id', $rootAlias), ':products'))
            ->setParameter('products', $productIds, ArrayParameterType::INTEGER);

        if ($promotionOrganization) {
            $queryBuilder
                ->andWhere(
                    $queryBuilder->expr()->eq(QueryBuilderUtil::sprintf('%s.organization', $rootAlias), ':organization')
                )
                ->setParameter('organization', $promotionOrganization->getId());
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

    private function getRootAlias(QueryBuilder $queryBuilder): string
    {
        $rootAliases = $queryBuilder->getRootAliases();
        if (empty($rootAliases)) {
            throw new \RuntimeException('No root alias for segment\'s query builder');
        }

        return (string) reset($rootAliases);
    }
}
