<?php

namespace Oro\Bundle\ProductBundle\Layout\DataProvider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Layout\SegmentProducts\SegmentProductsQueryProvider;
use Oro\Bundle\ProductBundle\Model\ProductView;
use Oro\Bundle\ProductBundle\Provider\ProductListBuilder;
use Oro\Bundle\ProductBundle\Provider\ProductSegmentProvider;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SegmentBundle\Entity\Segment;

/**
 * Provides featured products.
 */
class FeaturedProductsProvider
{
    private const PRODUCT_LIST_TYPE = 'featured_products';

    private SegmentProductsQueryProvider $segmentProductsQueryProvider;
    private ProductSegmentProvider $productSegmentProvider;
    private ProductListBuilder $productListBuilder;
    private AclHelper $aclHelper;
    private ConfigManager $configManager;

    /** @var array|null [product view, ...] */
    private ?array $products = null;

    public function __construct(
        SegmentProductsQueryProvider $segmentProductsQueryProvider,
        ProductSegmentProvider $productSegmentProvider,
        ProductListBuilder $productListBuilder,
        AclHelper $aclHelper,
        ConfigManager $configManager
    ) {
        $this->segmentProductsQueryProvider = $segmentProductsQueryProvider;
        $this->productSegmentProvider = $productSegmentProvider;
        $this->productListBuilder = $productListBuilder;
        $this->aclHelper = $aclHelper;
        $this->configManager = $configManager;
    }

    /**
     * @return ProductView[]
     */
    public function getProducts(): array
    {
        if (null === $this->products) {
            $this->products = $this->loadProducts();
        }

        return $this->products;
    }

    private function loadProducts(): array
    {
        $segment = $this->getSegment();
        if (null === $segment) {
            return [];
        }

        $query = $this->segmentProductsQueryProvider->getQuery($segment, self::PRODUCT_LIST_TYPE);
        if (null === $query) {
            return [];
        }

        $this->aclHelper->apply($query);
        $rows = $query->getArrayResult();
        if (!$rows) {
            return [];
        }

        return $this->productListBuilder->getProductsByIds(self::PRODUCT_LIST_TYPE, array_column($rows, 'id'));
    }

    private function getSegment(): ?Segment
    {
        $segmentId = $this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::FEATURED_PRODUCTS_SEGMENT_ID)
        );
        if (!$segmentId) {
            return null;
        }

        return $this->productSegmentProvider->getProductSegmentById($segmentId);
    }
}
