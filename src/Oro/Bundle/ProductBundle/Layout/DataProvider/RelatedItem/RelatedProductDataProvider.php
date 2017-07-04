<?php

namespace Oro\Bundle\ProductBundle\Layout\DataProvider\RelatedItem;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\Restriction\RestrictedProductRepository;
use Oro\Bundle\ProductBundle\RelatedItem\AbstractRelatedItemConfigProvider;
use Oro\Bundle\ProductBundle\RelatedItem\FinderStrategyInterface;

class RelatedProductDataProvider
{
    /** @var FinderStrategyInterface */
    private $finderStrategy;

    /** @var AbstractRelatedItemConfigProvider */
    private $configProvider;

    /** @var RestrictedProductRepository */
    private $restrictedProductRepository;

    /**
     * @param FinderStrategyInterface           $finderStrategy
     * @param AbstractRelatedItemConfigProvider $configProvider
     * @param RestrictedProductRepository       $restrictedProductRepository
     */
    public function __construct(
        FinderStrategyInterface $finderStrategy,
        AbstractRelatedItemConfigProvider $configProvider,
        RestrictedProductRepository $restrictedProductRepository
    ) {
        $this->finderStrategy = $finderStrategy;
        $this->configProvider = $configProvider;
        $this->restrictedProductRepository = $restrictedProductRepository;
    }

    /**
     * @param Product $product
     * @return Product[]
     */
    public function getRelatedProducts(Product $product)
    {
        /** @var Product[] $relatedProducts */
        $relatedProducts = $this->finderStrategy->find($product);

        if (!$this->hasMoreThanRequiredMinimum($relatedProducts)) {
            return [];
        }

        $relatedProductsId = [];
        foreach ($relatedProducts as $relatedProduct) {
            $relatedProductsId[] = $relatedProduct->getId();
        }

        $restrictedProducts = $this->restrictedProductRepository->findProducts(
            $relatedProductsId,
            $this->configProvider->getMaximumItems()
        );

        if (!$this->hasMoreThanRequiredMinimum($restrictedProducts)) {
            return [];
        }

        return $restrictedProducts;
    }

    /**
     * @param Product[] $relatedProducts
     * @return bool
     */
    private function hasMoreThanRequiredMinimum($relatedProducts)
    {
        return count($relatedProducts) >= (int)$this->configProvider->getMinimumItems();
    }
}
