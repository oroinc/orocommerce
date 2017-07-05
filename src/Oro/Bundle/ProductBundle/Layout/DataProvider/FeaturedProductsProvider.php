<?php

namespace Oro\Bundle\ProductBundle\Layout\DataProvider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Provider\ProductsProviderInterface;
use Oro\Bundle\ProductBundle\Provider\Segment\ProductSegmentProviderInterface;
use Oro\Bundle\SegmentBundle\Entity\Manager\SegmentManager;
use Oro\Bundle\SegmentBundle\Entity\Segment;

class FeaturedProductsProvider implements ProductsProviderInterface
{
    const FEATURED_PRODUCTS_CACHE_KEY = 'oro_product.layout.data_provider.featured_products_featured_products';

    /**
     * @var SegmentManager
     */
    private $segmentManager;

    /**
     * @var ProductManager
     */
    private $productManager;

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var ProductSegmentProviderInterface
     */
    private $productSegmentProvider;

    /**
     * @param SegmentManager                  $segmentManager
     * @param ProductSegmentProviderInterface $productSegmentProvider
     * @param ProductManager                  $productManager
     * @param ConfigManager                   $configManager
     */
    public function __construct(
        SegmentManager $segmentManager,
        ProductSegmentProviderInterface $productSegmentProvider,
        ProductManager $productManager,
        ConfigManager $configManager
    ) {
        $this->segmentManager = $segmentManager;
        $this->productSegmentProvider = $productSegmentProvider;
        $this->productManager = $productManager;
        $this->configManager = $configManager;
    }

    /**
     * {@inheritDoc}
     */
    public function getProducts()
    {
        $segment = $this->getSegment();
        if ($segment) {
            $qb = $this->segmentManager->getEntityQueryBuilder($segment);
            if ($qb) {
                return $this->productManager->restrictQueryBuilder($qb, [])->getQuery()->getResult();
            }
        }

        return [];
    }

    /**
     * @return Segment|null
     */
    private function getSegment()
    {
        $segmentId = $this->configManager
            ->get(sprintf('%s.%s', Configuration::ROOT_NODE, Configuration::FEATURED_PRODUCTS_SEGMENT_ID));
        if ($segmentId) {
            $segment = $this->productSegmentProvider->getProductSegmentById($segmentId);

            if ($segment) {
                return $segment;
            }
        }

        return null;
    }
}
