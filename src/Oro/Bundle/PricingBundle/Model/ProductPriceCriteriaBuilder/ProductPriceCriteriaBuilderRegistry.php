<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaBuilder;

use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Collects product price criteria builders.
 */
class ProductPriceCriteriaBuilderRegistry
{
    /** @var iterable<ProductPriceCriteriaBuilderInterface> */
    private iterable $productPriceCriteriaBuilders;

    /**
     * @param iterable<ProductPriceCriteriaBuilderInterface> $productPriceCriteriaBuilders
     */
    public function __construct(iterable $productPriceCriteriaBuilders)
    {
        $this->productPriceCriteriaBuilders = $productPriceCriteriaBuilders;
    }

    public function getBuilderForProduct(Product $product): ProductPriceCriteriaBuilderInterface
    {
        foreach ($this->productPriceCriteriaBuilders as $productPriceCriteriaBuilder) {
            if ($productPriceCriteriaBuilder->isSupported($product)) {
                return clone $productPriceCriteriaBuilder;
            }
        }

        throw new \LogicException(
            sprintf('No applicable product price criteria builder is found for product #%d', $product->getId())
        );
    }
}
