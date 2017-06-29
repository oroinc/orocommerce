<?php

namespace Oro\Bundle\ProductBundle\Layout\DataProvider;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\ChainCache;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SegmentBundle\Entity\Manager\SegmentManager;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Psr\Log\LoggerInterface;

class FeaturedProductsProvider
{
    const FEATURED_PRODUCTS_CACHE_KEY = 'oro_product.layout.data_provider.featured_products_featured_products';

    /**
     * @var ChainCache
     */
    private $cache;

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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param SegmentManager  $segmentManager
     * @param ProductManager  $productManager
     * @param ConfigManager   $configManager
     * @param LoggerInterface $logger
     * @param Cache           $cache
     */
    public function __construct(
        SegmentManager $segmentManager,
        ProductManager $productManager,
        ConfigManager $configManager,
        LoggerInterface $logger,
        Cache $cache
    ) {
        $this->segmentManager = $segmentManager;
        $this->productManager = $productManager;
        $this->configManager = $configManager;
        $this->logger = $logger;
        $this->cache = $cache;
    }

    /**
     * @return array
     */
    public function getAll()
    {
        $items = [];
        if ($this->cache->contains(static::FEATURED_PRODUCTS_CACHE_KEY)) {
            $items = $this->cache->fetch(static::FEATURED_PRODUCTS_CACHE_KEY);
        } else {
            $items = $this->fetchProducts();
            $this->cache->save(static::FEATURED_PRODUCTS_CACHE_KEY, $items);
        }

        return $items;
    }

    /**
     * @return array|Product[]
     */
    private function fetchProducts()
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
            $segment = $this->segmentManager->findById($segmentId);
            if ($segment && $segment->getEntity() !== Product::class) {
                $this->logger->error(
                    sprintf('Expected "%s", but "%s" is given.', Product::class, $segment->getEntity()),
                    [
                        'id' => $segment->getId(),
                        'name' => $segment->getName(),
                        'entity' => $segment->getEntity(),
                        'type' => $segment->getType()->getName(),
                    ]
                );

                return null;
            }

            return $segment;
        }

        return null;
    }
}
