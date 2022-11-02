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

    public function __construct(
        ManagerRegistry $doctrine,
        ProductVisibilityQueryBuilderModifier $queryBuilderModifier
    ) {
        $this->doctrine = $doctrine;
        $this->queryBuilderModifier = $queryBuilderModifier;
    }

    public function isVisible(int $productId): ?bool
    {
        if (!isset($this->cache[$productId])) {
            $this->prefetch([$productId]);
        }

        return $this->cache[$productId];
    }

    /**
     * Prefetches visibility state for given products ids.
     */
    public function prefetch(array $productIds): void
    {
        $productIds = array_diff($productIds, array_keys($this->cache));
        if (!$productIds) {
            return;
        }

        /** @var ProductRepository $repository */
        $repository = $this->doctrine->getManagerForClass(Product::class)->getRepository(Product::class);

        $qb = $repository->getProductsQueryBuilder($productIds);
        $this->queryBuilderModifier->modify($qb);
        $qb->resetDQLPart('select')->select('p.id');
        $visibleProducts = array_column($qb->getQuery()->getResult(AbstractQuery::HYDRATE_ARRAY), 'id');

        $this->cache = array_replace(
            $this->cache,
            array_fill_keys($productIds, false),
            array_fill_keys($visibleProducts, true)
        );
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
