<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaBuilder;

use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

/**
 * Interface for product price criteria builders.
 */
interface ProductPriceCriteriaBuilderInterface
{
    public function create(): ?ProductPriceCriteria;

    public function setProduct(?Product $product): self;

    public function setProductUnit(?ProductUnit $productUnit): self;

    public function setProductUnitCode(string $productUnitCode): self;

    public function setQuantity(?float $quantity): self;

    public function setCurrency(?string $currency): self;

    /**
     * Whether the builder is able to create a product price criteria for $product.
     */
    public function isSupported(Product $product): bool;
}
