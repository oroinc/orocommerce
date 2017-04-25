<?php

namespace Oro\Bundle\OrderBundle\Provider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;

class SkuCachedProductProvider
{
    /**
     * @var string[]
     */
    private $cachedSkus = [];

    /**
     * @var Product[]
     */
    private $cachedProductsBySku;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @param ProductRepository $productRepository
     */
    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * @param string $sku
     *
     * @return $this
     */
    public function addSkuToCache($sku)
    {
        if (in_array($sku, $this->cachedSkus)) {
            return $this;
        }

        $this->cachedSkus[] = $sku;

        return $this;
    }

    /**
     * @param $sku
     *
     * @return null|Product
     */
    public function getBySku($sku)
    {
        if (false === in_array($sku, $this->cachedSkus)) {
            return $this->productRepository->findOneBySku($sku);
        }

        if (null === $this->cachedProductsBySku) {
            $this->cacheProductsByAllCachedSkus();
        }

        if (false === array_key_exists($sku, $this->cachedProductsBySku)) {
            return null;
        }

        return $this->cachedProductsBySku[$sku];
    }

    private function cacheProductsByAllCachedSkus()
    {
        $this->cachedProductsBySku = [];

        /** @var Product[] $products */
        $products = $this->productRepository->findBy(['sku' => $this->cachedSkus]);

        foreach ($products as $product) {
            $this->cachedProductsBySku[$product->getSku()] = $product;
        }
    }
}
