<?php

namespace Oro\Bundle\ProductBundle\Layout\SegmentProducts;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\SegmentBundle\Entity\Manager\SegmentManager;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Provides queries that are used to retrieve segment products.
 * Also this provider caches these queries.
 */
class SegmentProductsQueryProvider
{
    private TokenStorageInterface $tokenStorage;
    private WebsiteManager $websiteManager;
    private SegmentManager $segmentManager;
    private ProductManager $productManager;
    private SegmentProductsQueryCache $cache;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        WebsiteManager $websiteManager,
        SegmentManager $segmentManager,
        ProductManager $productManager,
        SegmentProductsQueryCache $cache
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->websiteManager = $websiteManager;
        $this->segmentManager = $segmentManager;
        $this->productManager = $productManager;
        $this->cache = $cache;
    }

    public function getQuery(Segment $segment, string $queryType): ?Query
    {
        $cacheKey = $this->getCacheKey($segment, $queryType);
        $query = $this->cache->getQuery($cacheKey);
        if (null === $query) {
            $qb = $this->getQueryBuilder($segment);
            if (null !== $qb) {
                $query = $qb->getQuery();
                $this->cache->setQuery($cacheKey, $query);
            }
        }

        return $query;
    }

    private function getCacheKey(Segment $segment, string $queryType): string
    {
        $user = $this->tokenStorage->getToken()?->getUser();
        $website = $this->websiteManager->getCurrentWebsite();

        return implode('_', [
            $queryType,
            $user instanceof AbstractUser ? $user->getId() : 0,
            $website ? $website->getId() : 0,
            $segment->getId(),
            $segment->getRecordsLimit()
        ]);
    }

    private function getQueryBuilder(Segment $segment): ?QueryBuilder
    {
        $qb = $this->segmentManager->getEntityQueryBuilder($segment);
        if ($qb) {
            $qb->select('u.id');
            $this->productManager->restrictQueryBuilder($qb, []);
        }

        return $qb;
    }
}
