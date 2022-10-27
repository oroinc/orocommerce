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
 * Provides new arrivals products.
 */
class NewArrivalsProvider
{
    private const PRODUCT_LIST_TYPE = 'new_arrivals';

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
        $maxItemsLimit = $this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::NEW_ARRIVALS_MAX_ITEMS)
        );
        $minItemsLimit = $this->getMinItemsLimit($maxItemsLimit);
        if (null === $minItemsLimit) {
            // limits are not valid
            return [];
        }

        $segment = $this->getSegment();
        if (null === $segment) {
            return [];
        }

        $query = $this->segmentProductsQueryProvider->getQuery($segment, self::PRODUCT_LIST_TYPE);
        if (null === $query) {
            return [];
        }

        $this->aclHelper->apply($query);
        if (null !== $maxItemsLimit) {
            $query->setMaxResults($maxItemsLimit);
        }
        $rows = $query->getArrayResult();
        if (!$rows || (count($rows) < $minItemsLimit)) {
            return [];
        }

        return $this->productListBuilder->getProductsByIds(self::PRODUCT_LIST_TYPE, array_column($rows, 'id'));
    }

    private function getMinItemsLimit(?int $maxItemsLimit): ?int
    {
        if (null === $maxItemsLimit) {
            // if max limit is null, it is mean that there are no limits
            return 0;
        }
        if ($maxItemsLimit <= 0) {
            // max limit is invalid, return null to indicate that limits are not valid
            return null;
        }

        // if min limit is null, then we can operate it like zero
        $minItemsLimit = (int)$this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::NEW_ARRIVALS_MIN_ITEMS)
        );
        if ($maxItemsLimit < $minItemsLimit) {
            // max limit must be greater or equal to min limit, return null to indicate that limits are not valid
            return null;
        }

        return $minItemsLimit;
    }

    private function getSegment(): ?Segment
    {
        $segmentId = $this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::NEW_ARRIVALS_PRODUCT_SEGMENT_ID)
        );
        if (!$segmentId) {
            return null;
        }

        return $this->productSegmentProvider->getProductSegmentById($segmentId);
    }
}
