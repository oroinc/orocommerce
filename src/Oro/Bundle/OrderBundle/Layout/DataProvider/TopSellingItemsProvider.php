<?php

namespace Oro\Bundle\OrderBundle\Layout\DataProvider;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\ChainCache;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;

class TopSellingItemsProvider
{
    const DEFAULT_QUANTITY = 10;
    const TOP_SELLING_ITEMS_CACHE_KEY = 'oro_order.layout.provider.top_selling_items_top_selling_items';

    /**
     * @var ChainCache
     */
    private $cache;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var ProductManager
     */
    protected $productManager;

    /**
     * @param ProductRepository $productRepository
     * @param ProductManager    $productManager
     * @param Cache             $cache
     */
    public function __construct(
        ProductRepository $productRepository,
        ProductManager $productManager,
        Cache $cache
    ) {
        $this->productRepository = $productRepository;
        $this->productManager = $productManager;
        $this->cache = $cache;
    }

    /**
     * @param int $quantity
     *
     * @return Product[]
     */
    public function getAll($quantity = self::DEFAULT_QUANTITY)
    {
        $items = [];
        if ($this->cache->contains(static::TOP_SELLING_ITEMS_CACHE_KEY)) {
            $items = $this->cache->fetch(static::TOP_SELLING_ITEMS_CACHE_KEY);
        } else {
            $queryBuilder = $this->productRepository->getFeaturedProductsQueryBuilder($quantity);
            $this->productManager->restrictQueryBuilder($queryBuilder, []);
            $items = $queryBuilder->getQuery()->getResult();
            $this->cache->save(static::TOP_SELLING_ITEMS_CACHE_KEY, $items);
        }

        return $items;
    }
}
