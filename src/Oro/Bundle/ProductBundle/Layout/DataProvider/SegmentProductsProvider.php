<?php

namespace Oro\Bundle\ProductBundle\Layout\DataProvider;

use Oro\Bundle\ProductBundle\Layout\SegmentProducts\SegmentProductsQueryProvider;
use Oro\Bundle\ProductBundle\Model\ProductView;
use Oro\Bundle\ProductBundle\Provider\ProductListBuilder;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SegmentBundle\Entity\Segment;

/**
 * Provides products for a specific segment.
 */
class SegmentProductsProvider
{
    private const PRODUCT_LIST_TYPE = 'segment_products';

    private SegmentProductsQueryProvider $segmentProductsQueryProvider;
    private ProductListBuilder $productListBuilder;
    private AclHelper $aclHelper;

    /** @var array [cache key => [product view, ...], ...] */
    private array $products = [];

    public function __construct(
        SegmentProductsQueryProvider $segmentProductsQueryProvider,
        ProductListBuilder $productListBuilder,
        AclHelper $aclHelper
    ) {
        $this->segmentProductsQueryProvider = $segmentProductsQueryProvider;
        $this->productListBuilder = $productListBuilder;
        $this->aclHelper = $aclHelper;
    }

    /**
     * @param Segment $segment
     * @param int     $minItemsLimit
     * @param int     $maxItemsLimit
     *
     * @return ProductView[]
     */
    public function getProducts(Segment $segment, int $minItemsLimit, int $maxItemsLimit): array
    {
        $cacheKey = sprintf('%d_%d_%d', $segment->getId(), $minItemsLimit, $maxItemsLimit);
        if (!isset($this->products[$cacheKey])) {
            $this->products[$cacheKey] = $this->loadProducts($segment, $minItemsLimit, $maxItemsLimit);
        }

        return $this->products[$cacheKey];
    }

    public function loadProducts(Segment $segment, int $minItemsLimit, int $maxItemsLimit): array
    {
        if ($maxItemsLimit <= 0 || $maxItemsLimit < $minItemsLimit) {
            return [];
        }

        $query = $this->segmentProductsQueryProvider->getQuery($segment, self::PRODUCT_LIST_TYPE);
        if (null === $query) {
            return [];
        }

        $this->aclHelper->apply($query);
        $query->setMaxResults($maxItemsLimit);
        $rows = $query->getArrayResult();
        if (!$rows || (count($rows) < $minItemsLimit)) {
            return [];
        }

        return $this->productListBuilder->getProductsByIds(self::PRODUCT_LIST_TYPE, array_column($rows, 'id'));
    }
}
