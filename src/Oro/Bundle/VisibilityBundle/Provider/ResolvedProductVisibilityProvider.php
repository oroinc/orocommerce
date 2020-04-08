<?php

namespace Oro\Bundle\VisibilityBundle\Provider;

use Doctrine\ORM\AbstractQuery;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\VisibilityBundle\Model\ProductVisibilityQueryBuilderModifier;

/**
 * Product visibility provider with ability prefetch visibility for multiple products.
 * Aimed to reduce number of duplicated queries in cases where many products are displayed at once.
 */
class ResolvedProductVisibilityProvider
{
    /** @var ManagerRegistry */
    private $doctrine;

    /** @var ProductVisibilityQueryBuilderModifier */
    private $queryBuilderModifier;

    /** @var array */
    private $cache = [];

    /**
     * @param ManagerRegistry $doctrine
     * @param ProductVisibilityQueryBuilderModifier $queryBuilderModifier
     */
    public function __construct(
        ManagerRegistry $doctrine,
        ProductVisibilityQueryBuilderModifier $queryBuilderModifier
    ) {
        $this->doctrine = $doctrine;
        $this->queryBuilderModifier = $queryBuilderModifier;
    }

    /**
     * @param int $productId
     *
     * @return bool|null
     */
    public function isVisible(int $productId): ?bool
    {
        if (!isset($this->cache[$productId])) {
            $this->prefetch([$productId]);
        }

        return $this->cache[$productId];
    }

    /**
     * Prefetches visibility state for given products ids.
     *
     * @param array $productIds
     */
    public function prefetch(array $productIds): void
    {
        if (!$productIds) {
            return;
        }

        /** @var ProductRepository $repository */
        $repository = $this->doctrine->getManagerForClass(Product::class)->getRepository(Product::class);

        $qb = $repository->getProductsQueryBuilder($productIds);
        $this->queryBuilderModifier->modify($qb);
        $qb->resetDQLPart('select')->select('p.id');
        $visibleProducts = array_column($qb->getQuery()->getResult(AbstractQuery::HYDRATE_ARRAY), 'id', 'id');

        $result = [];
        foreach ($productIds as $productId) {
            $result[$productId] = isset($visibleProducts[$productId]);
        }

        $this->cache = array_replace($this->cache, $result);
    }

    /**
     * @param int|string|null $id
     */
    public function clearCache($id = null): void
    {
        if ($id === null) {
            $this->cache = [];
        } else {
            unset($this->cache[$id]);
        }
    }
}
