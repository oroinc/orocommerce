<?php

namespace Oro\Bundle\OrderBundle\Provider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * Product local cache by sku.
 */
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
     * @var AclHelper
     */
    private $aclHelper;

    /**
     * @param ProductRepository $productRepository
     * @param AclHelper $aclHelper
     */
    public function __construct(ProductRepository $productRepository, AclHelper $aclHelper)
    {
        $this->productRepository = $productRepository;
        $this->aclHelper = $aclHelper;
    }

    /**
     * @param string $sku
     *
     * @return $this
     */
    public function addSkuToCache($sku)
    {
        if (in_array($sku, $this->cachedSkus, true)) {
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
        if (false === in_array($sku, $this->cachedSkus, true)) {
            $qb = $this->productRepository->getBySkuQueryBuilder($sku);

            return $this->aclHelper->apply($qb)->getOneOrNullResult();
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

        $qb = $this->productRepository->getBySkuQueryBuilder($this->cachedSkus);

        /** @var Product[] $products */
        $products = $this->aclHelper->apply($qb)->getResult();
        foreach ($products as $product) {
            $this->cachedProductsBySku[$product->getSku()] = $product;
        }
    }
}
